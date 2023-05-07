<?php


namespace app\domain\recall\service;


use app\common\GetuiCommon;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\notice\dao\PushTemplateModelDao;
use app\domain\notice\model\PushTemplateModel;
use app\domain\recall\dao\PushRecallConfModelDao;
use app\domain\recall\model\PushRecallConfModel;
use app\domain\recall\model\PushRecallType;
use app\domain\recall\queue\AmpQueue;
use app\domain\sms\api\RongtongdaSmsApi;
use app\domain\user\dao\MemberDetailModelDao;
use app\domain\user\dao\MemberRecallModelDao;
use app\domain\user\dao\MemberRecallUserModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\MemberRecallModel;
use app\domain\user\model\MemberRecallUserModel;
use app\event\UserLoginEvent;
use app\utils\CommonUtil;
use think\facade\Log;

class MemberRecallService
{
    protected static $instance;
    private $limitFlowKey = "userLimitFlowkey";
    protected $serviceName = 'userMaster';
    protected $table = 'zb_member';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberRecallService();
        }
        return self::$instance;
    }

    /**
     * @return string
     */
    private function getHistoryUserKey()
    {
        return sprintf('user_online_history_%s_list', 'all');
    }

    public function createQueue(PushRecallConfModel $recallmodel)
    {
        $jsonRecallModel = $recallmodel->modelToData();
        $strData = json_encode($jsonRecallModel);
        Log::info(sprintf("MemberRecallService entry createQueue:%s", $strData));
        return AmpQueue::getInstance()->publisher($strData);
    }


    /**
     * @Info 根据用户信息做过滤
     * @param $uids
     * @param $chargeMax int 最大充值金额
     * @param $chargeMin int 最小充值金额
     * @param $pushType
     * @return array
     */
    private function filterUser($uids, PushRecallConfModel $parseModel)
    {
//        if (CommonUtil::getAppDev()) {
//            return $uids;
//        }
        $chargeMax = $parseModel->pushWhen->chargeMax ?? 0;
        $chargeMin = $parseModel->pushWhen->chargeMin ?? 0;
        $pushTime = $parseModel->pushWhen->time ?? 0;
        $pushType = $parseModel->pushType;
//        过滤充值金额
        if ($chargeMax || $chargeMin) {
            $uids = MemberDetailModelDao::getInstance()->filterChargeUsers($uids, $chargeMax, $chargeMin);
            if (empty($uids)) {
                return [];
            }
        }
        $userRegisterMap = UserModelDao::getInstance()->findUserModelMapByUserIds($uids);
//        如果是短信推送，注册30天以上的用户为老用户，处理30天内的用户数据时，不触达老用户
        if ($parseModel->pushType === PushRecallType::$RTDSMS) {
            $uids = $this->rtdsmsFilterUser($uids, $userRegisterMap, $pushTime);
            if (empty($uids)) {
                return [];
            }
        }

//        过滤频次
        $result = [];
        foreach ($uids as $userId) {
            try {
                $this->limitFlowUserId($userId, $pushType);
            } catch (\Exception $e) {
                continue;
            }
            $result[] = $userId;
        }
        return $result;
    }

    private function getUnixTime()
    {
        return time();
    }

    private function getStrDateTime()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * @Info 过滤rtd短信的用户id
     * @param $uids
     * @param $userRegisterList
     * @param $pushTime
     * @return array
     * @throws \Exception
     */
    private function rtdsmsFilterUser($uids, $userRegisterMap, $pushTime)
    {
        if ($pushTime >= 2592000) {
            return $uids;
        }
        $loalStrDateTime = $this->getStrDateTime();
        $result = [];
        foreach ($userRegisterMap as $userId => $userModel) {
            $registerTimeStr = $userModel->registerTime ?date("Y-m-d H:i:s",$userModel->registerTime): "";
            if ($registerTimeStr === "") {
                continue;
            }
            $datetime_register = new \DateTime($registerTimeStr);
            $datetime_load = new \DateTime($loalStrDateTime);
            $days = $datetime_register->diff($datetime_load)->days;
            if ($days > 0 && $days < 30) {
                $result[] = $userId;
            }
        }
        return $result;
    }


    /**
     * @info 根据投递方式进行用户过滤
     * @param $userId
     * @param $pushType
     * @throws FQException
     */
    private function limitFlowUserId($userId, $pushType)
    {
//            userLimitFlowkey_type=getuipush_userid=1178493_3600
        $cacheKey = sprintf("%s_type=%s_userid=%s", $this->limitFlowKey, $pushType, $userId);
//        个推 当日的离线用户维度 1 小时内push上限1条， 一天内push上限5条
        if ($pushType === PushRecallType::$GETUIPUSH) {
            $rules = [
                3400 => 1,
                86400 => 5,
            ];
            $server = new \app\common\server\LimitFlow($cacheKey, $rules);
            if ($server->isPass()) {
                throw new FQException('用户投递频繁，不能投递', 500);
            }
        }

//        短信 当日的离线用户维度 1 小时内push上限1条， 一天内push上限5条
        if ($pushType === PushRecallType::$CHUANGLANSMS || $pushType === PushRecallType::$RTDSMS) {
            $rules = [
                86400 => 1,
            ];
            $server = new \app\common\server\LimitFlow($cacheKey, $rules);
            if ($server->isPass()) {
                throw new FQException('用户投递频繁，不能投递', 500);
            }
        }
    }

    /**
     * @info load用户
     * 1天以内:扫描86400秒内离  线的热用户
     * 1天以上，30天以内：扫描大于当前时间节点-30天区间的所有用户
     * 超过30天：扫描30天以上所有用户
     * @param PushRecallConfModel $recallmodel
     * @param int $limit
     * @return \Generator|void
     */
    private function LoadUsers(PushRecallConfModel $recallmodel, $limit = 1000)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $iteerator = null;
        $pushWhenTime = $recallmodel->pushWhen->time;

//        如果是超过30天的
        if ($pushWhenTime >= 2592000) {
            $i = 0;
            $minId = null;
            $dbModelList = Sharding::getInstance()->getServiceModels($this->serviceName, $this->table);
            while (true) {
                if ($minId !== null && $minId < 10000) {
                    break;
                }
                Log::info(sprintf("MemberRecallService LoadUsers range page:%d minId:%d limit:%d", $i, $minId, $limit));
                $i += 1;
                $tempMinIdArr = [];
                try {
                    foreach ($dbModelList as $dbModel) {
                        list($arrDatas, $tempMinId) = UserModelDao::getInstance()->getLangTimeNotLoginUserIds($dbModel, $limit, $minId);
                        $tempMinIdArr[] = $tempMinId;
                        if (empty($arrDatas)) {
                            throw new FQException("load Empty", 5100);
                        }
                        usleep(100000);
                        yield $arrDatas;
                    }
                    $minId = min($tempMinIdArr);
                } catch (FQException $e) {
                    if ($e->getCode() === 5100) {
                        break;
                    }
                }
            }
            return;
        }

        list($start, $end) = $this->unixDayForScoreStart($pushWhenTime);
//        30天内的
        for ($i = 0; $i < 200; $i++) {
            $offset = $limit * $i;
            Log::info(sprintf("MemberRecallService LoadUsers range start:%s end:%s offset:%s limit:%d", date("Y-m-d H:i:s", $start), date("Y-m-d H:i:s", $end), $offset, $limit));
            $arrDatas = $redis->zRangeByScore($this->getHistoryUserKey(), $start, $end, array('withscores' => TRUE, 'limit' => array($offset, $limit)));
            if (empty($arrDatas)) {
                break;
            }
            yield $arrDatas;
            usleep(100000);
        }
    }

    /**
     * @param $pushWhenTime
     * @return array
     */
    private function unixDayForScoreStart($pushWhenTime)
    {
        $unixTime = time();
        if ($pushWhenTime < 86400) {
            $startTime = $unixTime - 86400;
            $endTime = $unixTime - $pushWhenTime;
            return [$startTime, $endTime];
        }
//        超过1天的
        $startTime = $unixTime - 2592000;
        $endTime = $unixTime - $pushWhenTime;
        return [$startTime, $endTime];
    }

    /**
     * @任务解析 消息队列消费者
     * @param $msg
     */
    public function taskConsumer($msg)
    {
        try {
            $msgBody = $msg->body;
            $this->filterComsumerReload($msgBody, 5, 3500);

            $parseRes = json_decode($msgBody, true);
            $result = 0;
            if ($parseRes) {
                $result = $this->handlerQueueConsumer($parseRes);
            }
            Log::info(sprintf("AmpQueue Consumer commindName=%s success body=%s result=%s", "taskConsumer", $msgBody, $result));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        } catch (\Throwable $e) {
            Log::error(sprintf("AmpQueue Consumer error commondName=%s err=%d errmsg=%s strace=%s file=%s lens=%d", "taskConsumer", $e->getCode(), $e->getMessage(), $e->getTraceAsString(), $e->getFile(), $e->getLine()));

            if ($e instanceof FQException && $e->getCode() === 513) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                return;
            }
        }
    }

//    /**
//     * @info 执行sms task的消费者
//     * @param $parseRes
//     * @return bool
//     * @example {"id":4,"push_when":{"charge_max":500,"charge_min":100,"time":3600},"push_type":"getuipush","template_ids":"[3,4]"}
//     */
//    public function handlerSmSTaskConsumer($parseRes)
//    {
//        $parseModel = PushRecallConfModelDao::getInstance()->dataToModel($parseRes);
////        查询用户id
//        $generator = $this->loadUsers($parseModel);
//        foreach ($generator as $userDatas) {
//            if (empty($userDatas)) {
//                Log::info("handlerSmSTaskConsumer userDatas empty");
//                break;
//            }
//            Log::info(sprintf("MemberRecallService handlerSmSTaskConsumer generator data:%s", json_encode($userDatas)));
////            过滤是否为发送时间节点
//            $userIds = $this->filterActive($userDatas, $parseModel);
//            if (empty($userIds)) {
//                Log::info("handlerSmSTaskConsumer filterActive not find more userData");
//                continue;
//            }
////            根据用户信息过滤金额和发送频次
//            $userIds = $this->filterUser($userIds, $parseModel);
//            if (empty($userIds)) {
//                Log::info("not find more userData");
//                continue;
//            }
//
//            Log::info(sprintf("handlerSmSTaskConsumer filter success userid=%s", json_encode($userIds)));
////            推送用户到队列
//            $this->publisherUserPush($userIds, $parseRes);
//            if (CommonUtil::getAppDev()) {
//                return true;
//            }
//        }
//        return true;
//    }


    /**
     * @info 执行 消息队列消费者
     * @param $parseRes
     * @return bool
     * @example {"id":4,"push_when":{"charge_max":500,"charge_min":100,"time":3600},"push_type":"getuipush","template_ids":"[3,4]"}
     */
    public function handlerQueueConsumer($parseRes)
    {
        $parseModel = PushRecallConfModelDao::getInstance()->dataToModel($parseRes);
//        查询用户id
        $generator = $this->loadUsers($parseModel);
        foreach ($generator as $userDatas) {
            if (empty($userDatas)) {
                Log::info("handlerQueueConsumer userDatas empty");
                break;
            }
            Log::info(sprintf("MemberRecallService handlerQueueConsumer generator data:%s", json_encode($userDatas)));
//            过滤用户是否为发送时间节点
            $userIds = $this->filterActive($userDatas, $parseModel);
            if (empty($userIds)) {
                Log::info("handlerQueueConsumer filterActive not find more userData");
                continue;
            }
//            过滤金额和发送频次
            $userIds = $this->filterUser($userIds, $parseModel);
            if (empty($userIds)) {
                Log::info("not find more userData");
                continue;
            }
            Log::info(sprintf("handlerQueueConsumer filter success userid=%s", json_encode($userIds)));
//            推送用户到队列
            $this->publisherUserPush($userIds, $parseRes);
        }
        return true;
    }


    /**
     * @info 过滤是否为发送节点,
     * 30 天以上的用户，个推7天推送一次，sms10天推送1
     * @param $userDatas
     * @param $parseModel
     * @return array
     */
    private function filterActive($userDatas, PushRecallConfModel $parseModel)
    {
//        if (CommonUtil::getAppDev()) {
//            return array_keys($userDatas);
//        }

//        过滤发送节点
        $result = [];
        foreach ($userDatas as $userId => $unixTime) {
            try {
//                超过30天过滤是否推送
                if ($parseModel->pushWhen->time >= 2592000) {
//                    个推7天推送一次
                    if ($parseModel->pushType === PushRecallType::$GETUIPUSH) {
                        $rules = [
                            604800 => 1,
                        ];
                    }
//            短信10天推送一次
                    if ($parseModel->pushType === PushRecallType::$RTDSMS) {
                        $rules = [
                            864000 => 1,
                        ];
                    }
                    $cacheKey = sprintf("%s_type=%s_userid=%s_time=%d", $this->limitFlowKey, $parseModel->pushType, $userId, $parseModel->pushWhen->time);
                    $server = new \app\common\server\LimitFlow($cacheKey, $rules);
                    if ($server->isPass()) {
                        throw new FQException('用户投递频繁，不能投递', 500);
                    }
                }

//                30天内的用户，没有标记投递日期的不投递
                if ($parseModel->pushWhen->time < 2592000 && $parseModel->pushWhen->time >= 86400) {
                    if ($this->userloginTimeForPush($unixTime, $parseModel->pushType) === false) {
                        throw new FQException('用户投递频繁，不能投递', 500);
                    }
                }
//                1天内的用户 仅推送个推
                if ($parseModel->pushWhen->time < 86400) {
                    if ($parseModel->pushType !== PushRecallType::$GETUIPUSH) {
                        continue;
                    }
                    $this->userloginTimeForPushToday($userId, $unixTime);
                }
            } catch (\Exception $e) {
                continue;
            }
            $result[] = $userId;
        }
        return $result;
    }

    private function unixLoginTimeToday($unixTime)
    {
        $nowUnix = time();
        $dayUnix = (int)(($nowUnix - $unixTime) / 86400);
        if (empty($dayUnix)) {
            return 0;
        }
        return $dayUnix;
    }

    /**
     * @info 传入用户登录的时间戳，计算当前时间节点用户是否应该推送
     * @param $userId
     * @param $unixTime
     * @return bool
     * @throws FQException
     */
    public function userloginTimeForPushToday($userId, $unixTime)
    {
        $result = true;
        if (empty($unixTime)) {
            return $result;
        }
//        load today conf
        $config = PushRecallConfModelDao::getInstance()->loadTypeDayDataToday(PushRecallType::$GETUIPUSH);
        if (empty($config)) {
            return $result;
        }
        $configRules = array_keys($config);
        $configRules = array_flip($configRules);
        $rules = [];
        $n = 1;
        foreach ($configRules as $filter_time => $value) {
            $rules[(int)$filter_time] = $n;
            $n++;
        }
        if (empty($rules)) {
            return $result;
        }
//        userLimitFlowkey:userloginTimeForPushToday:user=1178493_10800
        $cacheKey = sprintf("%s:%s:user=%d", $this->limitFlowKey, "userloginTimeForPushToday", $userId);
        $server = new \app\common\server\LimitFlow($cacheKey, $rules);
        if ($server->isPass()) {
            throw new FQException('用户投递频繁，不能投递', 500);
        }
        return $result;
    }


    /**
     * @info 传入用户登录的时间戳，
     * @param $unixTime
     * @return bool fasle 不推 true推
     * @throws FQException
     */
    public function userloginTimeForPush($unixTime, $type)
    {
        $result = false;
        if (empty($unixTime)) {
            return $result;
        }

//        计算用户登录到现在差值天数
        $dayUnix = $this->UnixloginTimeToday($unixTime);
        if ($dayUnix === 0) {
            return $result;
        }
//        超过1天的
//        load 配置参数
        $conf = PushRecallConfModelDao::getInstance()->loadTypeDayData($type);
//        preg用户离线的天数 是否需要推送
        $result = isset($conf[$dayUnix]) ? true : false;
        return $result;
    }

    /**
     * @info 过滤重复提交的数据
     * @param $msgBody
     * @param $count
     * @throws FQException
     */
    private function filterComsumerReload($msgBody, $count, $ttl)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $filterKey = md5($msgBody);
        if ($redis->incr($filterKey) > $count) {
            throw new FQException(sprintf("fatal entry msg more times key=%s", $filterKey), 513);
        }
        $redis->expire($filterKey, $ttl);
    }

    /**
     * @info  用户消息推送 消费者
     * @param $msg
     */
    public function userPushConsumer($msg)
    {
        try {
            $msgBody = $msg->body;
            $this->filterComsumerReload($msgBody, 1, 86400);
            $parseRes = json_decode($msgBody, true);
            $result = 0;
            if ($parseRes) {
                $result = $this->handlerUserPushConsumer($parseRes);
            }
            Log::info(sprintf("AmpQueue Consumer commindName=%s success body=%s result=%s", "UserPushConsumer", $msgBody, json_encode($result)));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        } catch (\Throwable $e) {
            Log::error(sprintf("AmpQueue Consumer error commondName=%s err=%d errmsg=%s strace=%s file=%s lens=%d", "UserPushConsumer", $e->getCode(), $e->getMessage(), $e->getTraceAsString(), $e->getFile(), $e->getLine()));
            if ($e instanceof FQException && $e->getCode() === 513) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                return;
            }
        }
    }

    /**
     * @info 执行用户push消息推送
     * @param $originData
     * @throws FQException
     * @example {"push_recall_conf":"{\"id\":4,\"push_when\":{\"charge_max\":500,\"charge_min\":100,\"time\":3600},\"push_type\":\"getuipush\",\"template_ids\":\"[3,4]\"}","user_ids":"[1456410,1456408,1456402]"}
     */
    public function handlerUserPushConsumer($originData)
    {
        $userIds = isset($originData['user_ids']) ? json_decode($originData['user_ids'], true) : [];
        $pushRecallConfData = isset($originData['push_recall_conf']) ? json_decode($originData['push_recall_conf'], true) : [];
        if (empty($userIds) || empty($pushRecallConfData)) {
            throw new FQException("param error", 500);
        }
        $pushRecallConfModel = PushRecallConfModelDao::getInstance()->dataToModel($pushRecallConfData);
        $actionMap = [
            PushRecallType::$GETUIPUSH => 'handlerParseResUserPushGetuipush',
            PushRecallType::$RTDSMS => 'handlerParseResUserPushRtdsms',
        ];
        if (!isset($actionMap[$pushRecallConfModel->pushType])) {
            throw new FQException("handlerParseResUserPush action method error");
        }

        $func = $actionMap[$pushRecallConfModel->pushType];
        return $this->$func($pushRecallConfModel, $userIds);
    }

    /**
     * @info rtd短信 用户推送
     * @param PushRecallConfModel $pushRecallConfModel
     * @param $userIds
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function handlerParseResUserPushRtdsms(PushRecallConfModel $pushRecallConfModel, $userIds)
    {
        $memberRecallUserModelList = MemberRecallUserModelDao::getInstance()->findNicknameByUserIdByRecall($userIds);
        if ($memberRecallUserModelList === null) {
            throw new FQException("handlerParseResUserPushRtdsms load user data error", 513);
        }

//        初始化模版id
        $templateIds = $pushRecallConfModel->templateIds;
        shuffle($templateIds);
//        随机模版数据
        $templateId = current($templateIds);

        $templateModel = PushTemplateModelDao::getInstance()->loadModel($templateId);
        if ($templateModel === null) {
            throw new FQException("load push template error", 513);
        }

//        $link = 'www.rongqii.cn/{$userId}';
        $link = 'a.muayy.com/{$userId}';
        $sign = "音恋 语音";
        $snsResponseJson = $this->sendSMSListForTemplate($memberRecallUserModelList, $templateModel, $sign, $link);
        $snsResponseArr = json_decode($snsResponseJson, true);
        $snsId = $snsResponseArr['bid'] ?? "";
        $unixTime = time();
        $strDate = date("Ymd");

        //        入库用户召回模型
        $resetMemebrRecallModelList = [];
        foreach ($memberRecallUserModelList as $memberRecallUserModel) {
            if (!$memberRecallUserModel instanceof MemberRecallUserModel) {
                continue;
            }
            if ($memberRecallUserModel->username === "") {
                continue;
            }
            $resetMemebrRecallModelList[] = $this->storeMemberRecallModel($memberRecallUserModel, $snsResponseJson, $snsId, $pushRecallConfModel, $unixTime, $strDate);
        }
        return $resetMemebrRecallModelList;
    }


    /**
     * @param $userModelList
     * @param PushTemplateModel $templateModel
     * @param $sign
     * @param $urlTpl
     * @return string
     */
    public function sendSMSListForTemplate($userModelList, PushTemplateModel $templateModel, $sign, $urlTpl)
    {
        $varsArr = [];
        foreach ($userModelList as $userModel) {
            if (!$userModel instanceof MemberRecallUserModel) {
                continue;
            }
            if ($userModel->username === "") {
                continue;
            }
            $msg = $this->makeTemplate($templateModel, $userModel, $sign, $urlTpl);
            $itemVar = [
                'pn' => $userModel->username,
                'msg' => $msg,
            ];
            $varsArr[] = $itemVar;
        }
        $object = new RongtongdaSmsApi();
        return $object->sendVariableSMS($varsArr);
    }


    /**
     * @param PushTemplateModel $templateModel
     * @param MemberRecallUserModel $userModel
     * @param $sign
     * @param $urlTpl
     * @return string|string[]
     */
    private function makeTemplate(PushTemplateModel $templateModel, MemberRecallUserModel $userModel, $sign, $urlTpl)
    {
//        过滤用户昵称，去除emoji
        $userModel->nickname = filterEmoji($userModel->nickname);
        $userModel->nickname = mb_substr($userModel->nickname, 0, 7);
        $link = str_replace("{\$userId}", $userModel->userId, $urlTpl);
        return str_replace(["{\$sign}", "{\$nickname}", "{\$link}"], [$sign, $userModel->nickname, $link], $templateModel->content);
    }


    /**
     * @info 队列推送用户: 个推逻辑处理
     * @param PushRecallConfModel $pushRecallConfModel
     * @param $userIds
     * @return array
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function handlerParseResUserPushGetuipush(PushRecallConfModel $pushRecallConfModel, $userIds)
    {
        $memberRecallUserModelList = MemberRecallUserModelDao::getInstance()->findNicknameByUserIdByRecall($userIds);
        if ($memberRecallUserModelList === null) {
            throw new FQException("handlerParseResUserPushGetuipush load user data error", 513);
        }
//        初始化模版id
        $templateIds = $pushRecallConfModel->templateIds;
        shuffle($templateIds);
//        随机模版数据
        $templateId = current($templateIds);
        $templateModel = PushTemplateModelDao::getInstance()->loadModel($templateId);
        if ($templateModel === null) {
            throw new FQException("load push template error", 513);
        }
//        批量触发用户
        $type = $pushRecallConfModel->pushType;
        $title = $templateModel->title;
        $content = $templateModel->content;
        $pushRe = GetuiCommon::getInstance()->pushMessageToNotification($userIds, $type, '', $title, $content);
        $snsId = $pushRe['contentId'] ?? "";
        $snsResponseJson = json_encode($pushRe);
        $unixTime = time();
        $strDate = date("Ymd");
//        入库用户召回模型
        $resetMemebrRecallModelList = [];
        foreach ($memberRecallUserModelList as $memberRecallUserModel) {
            if (empty($memberRecallUserModel)) {
                continue;
            }
            $resetMemebrRecallModelList[] = $this->storeMemberRecallModel($memberRecallUserModel, $snsResponseJson, $snsId, $pushRecallConfModel, $unixTime, $strDate);
        }
        return $resetMemebrRecallModelList;
    }

    /**
     * @info 记录实力化的用户召回详情模型
     * @param MemberRecallUserModel $memberRecallUserModel
     * @param $snsResponseJson
     * @param $snsId
     * @param PushRecallConfModel $pushRecallConfModel
     * @param $unixTime
     * @param $strDate
     * @return int|string
     */
    private function storeMemberRecallModel(MemberRecallUserModel $memberRecallUserModel, $snsResponseJson, $snsId, PushRecallConfModel $pushRecallConfModel, $unixTime, $strDate)
    {
        $model = new MemberRecallModel;
        $model->userId = $memberRecallUserModel->userId;
        $model->originLoginTime = $memberRecallUserModel->loginTime;
        $model->chargeStatus = $memberRecallUserModel->amount ? 1 : 0;
        $model->amount = $memberRecallUserModel->amount ?: 0;
        $model->freeCoin = $memberRecallUserModel->freecoin;
        $model->coinBalance = $memberRecallUserModel->balance();
        $model->snsResponse = $snsResponseJson;
        $model->mobile = $memberRecallUserModel->username;
        $model->type = $pushRecallConfModel->pushType;
        $model->pushWhenTime = $pushRecallConfModel->pushWhen->time;
        $model->snsId = $snsId;
        $model->strDate = $strDate;
        $model->createTime = $unixTime;
        $model->updateTime = $unixTime;
        return MemberRecallModelDao::getInstance()->storeData($model);
    }

//            推送用户到队列
    private function publisherUserPush($userIds, $parseRes)
    {
        $data['push_recall_conf'] = json_encode($parseRes);
        $data['user_ids'] = json_encode($userIds);
        $strData = json_encode($data);
        Log::info(sprintf("MemberRecallService publisherUserPush store:%s", $strData));
        return AmpQueue::getInstance()->publisherUserPush($strData);
    }


    /**
     * @param UserLoginEvent $event
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
//        获取用户的最后一次召回请求
        $memberRecallModel = MemberRecallModelDao::getInstance()->findUserLastModel($event->userId);
        if ($memberRecallModel === null) {
            throw new FQException("not find user recall detail data", 500);
        }
//        过滤已经召回过的
        if ($memberRecallModel->recallLoginStatus === 1) {
            throw new FQException("error onUserLoginEvent user is recall", 500);
        }
//        标记为已召回成功，并且记录召回的登录时间
        $memberRecallModel->recallLoginStatus = 1;
        $memberRecallModel->loginTime = $event->timestamp;
        $upResult = MemberRecallModelDao::getInstance()->updateRecallData($memberRecallModel, $event->timestamp);

//        记录 用户召回成功info，和最后一次召回的条目id
        Log::info(sprintf('MemberRecallService::onUserLoginEvent success userId=%d recall_detail_id=%d result=%d', $event->userId, $memberRecallModel->id, $upResult));
        return true;
    }

}