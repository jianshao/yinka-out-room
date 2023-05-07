<?php


namespace app\domain\activity\recall;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetItem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\dao\RecallSmsDetailDao;
use app\domain\user\UserRepository;
use app\event\UserLoginEvent;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

//召回活动短信
class RecallSmsService
{
    protected static $instance;
    protected $filterRewardKey = "filterRecallSmsUser20211111";
    const COMMAND_NAME = "RecallQueueCommand";

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RecallSmsService();
        }
        return self::$instance;
    }

    /**
     * @param $conf
     * @return array|null
     */
    private function getRewards($conf)
    {
        $senderAssets = ArrayUtil::safeGet($conf, 'senderAssets', []);
        if ($senderAssets) {
            $senderAssetsModel = AssetItem::decodeList($senderAssets);
            return $senderAssetsModel;
        }

        return null;
    }


    private function sendReward($user, $rewards, $timestamp)
    {
        $userAssets = $user->getAssets();
        $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'recallSms');
        foreach ($rewards as $reward) {
            $userAssets->add($reward->assetId, $reward->count, $timestamp, $biEvent);
        }
    }

    /**
     * @param UserLoginEvent $event
     * @param $conf
     * @return array|bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function recallRewardImpl(UserLoginEvent $event, $conf)
    {
        $userId = $event->userId;
        $timeUnix = $event->timestamp;
//        初始化登陆查询时间
        $loginStartTimeStr = ArrayUtil::safeGet($conf, 'loginStartTime', '');
        $loginStartTime = TimeUtil::strToTime($loginStartTimeStr);
        if (empty($loginStartTime)) {
            throw new FQException("conf loginStartTime error", 500);
        }
//        7天内登陆过的用户过滤
        if ($event->lastLoginTime >= $loginStartTime) {
            throw new FQException("time condition of gift is not", 500);
        }

//        初始化是否已经领取过，领过则return
        $recallModel = RecallSmsDetailDao::getInstance()->findUserModel($userId);
        if ($recallModel && $recallModel->sendGift == 1) {
            throw new FQException("user already send", 500);
        }

//        获取奖品资产模型
        $rewards = $this->getRewards($conf);
        if (empty($rewards)) {
            throw new FQException("rewards error", 500);
        }

        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $rewards, $timeUnix) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
//            过滤活动一个用户只发一次
                $this->filterRecallUser($userId);
//            发奖励
                $this->sendReward($user, $rewards, $timeUnix);
            });

//        update 发短信的数据状态
            if ($recallModel && $recallModel->sendGift == 0) {
                $recallModel->updateTime = $timeUnix;
                $recallModel->loginTime = $timeUnix;
                $recallModel->sendGift = 1;
                RecallSmsDetailDao::getInstance()->updateRecallData($recallModel);
            }
            return $rewards;
        } catch (Exception $e) {
            Log::error(sprintf('recallSmsService recallRewardImpl userId=%d, ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }


    /**
     * @param UserLoginEvent $event
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recallReward(UserLoginEvent $event)
    {
        $conf = Config::loadRecallSmsConf();
        if (empty($conf)) {
            throw new FQException("recallReward sms conf error");
        }
//        检测活动是否开始
        $this->checkActivityTimeStart($conf);
        $rewards = $this->recallRewardImpl($event, $conf);
        $logInfo = [];
        foreach ($rewards as $reward) {
            $logInfo[] = $reward->name ? $reward->name : "";
        }
//            发小秘书消息
        $msg = ["msg" => sprintf("感谢您回来！送您%s，祝您玩的开心！", implode("", $logInfo))];
        $pushResult = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $event->userId, 'type' => 0, 'msg' => $msg]);
        Log::info(sprintf('RecallRewardsms ok userId=%d rewards=%s yunxinResult=%s', $event->userId, json_encode($logInfo), $pushResult));
        return true;
    }


    /**
     * @param $userId
     * @return bool
     * @throws FQException
     */
    private function filterRecallUser($userId)
    {
        $cacheKey = sprintf("%s:%d", $this->filterRewardKey, $userId);
        $redis = RedisCommon::getInstance()->getRedis();
        $incr = $redis->incr($cacheKey);
        if ($incr > 1) {
            throw new FQException("filterRecallUser sendGift");
        }
        $redis->expire($cacheKey, 864000);
        return true;
    }

    /**
     * @return bool
     * @throws FQException
     */
    public function checkActivityTime()
    {
        $conf = Config::loadRecallSmsConf();
        if ($conf == null) {
            throw new FQException("活动配置错误");
        }
        $this->checkActivityTimeStart($conf);
        return true;
    }

    /**
     * @param $conf
     * @throws FQException
     */
    private function checkActivityTimeStart($conf)
    {
        if (config("config.appDev") == "dev") {
            return true;
        }
        $timestamp = time();
        // 判断是否在活动期间
        $startTimeStr = ArrayUtil::safeGet($conf, 'startTime');
        $stopTimeStr = ArrayUtil::safeGet($conf, 'stopTime');

        if (!empty($startTimeStr)) {
            $startTime = TimeUtil::strToTime($startTimeStr);
            if ($timestamp < $startTime) {
                throw new FQException("活动没有开始");
            }
        }
        if (!empty($stopTimeStr)) {
            $stopTime = TimeUtil::strToTime($stopTimeStr);
            if ($timestamp >= $stopTime) {
                throw new FQException("活动已经结束了");
            }
        }
        return true;
    }


    /**
     * @info 获取需要发短信用户的时间临界点unixtime
     * @return int
     * @throws FQException
     */
    public function getLoginStartTimeUnix()
    {
        $conf = Config::loadRecallSmsConf();

        if ($conf == null) {
            throw new FQException("活动配置错误");
        }
        // 判断是否在活动期间
        $startTimeStr = ArrayUtil::safeGet($conf, 'loginStartTime');

        if (empty($startTimeStr)) {
            throw new FQException("配置错误", 500);
        }
        return $startTimeStr;
    }

}