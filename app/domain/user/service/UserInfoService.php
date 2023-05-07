<?php


namespace app\domain\user\service;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\sensors\service\SensorsUserService;
use app\domain\user\dao\MemberDetailAuditDao;
use app\domain\user\dao\NicknameLibraryDao;
use app\domain\user\dao\UserInfoMapDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\user\model\UserModel;
use app\domain\user\UserRepository;
use app\event\MemberDetailAuditEvent;
use app\query\user\cache\MemberDetailAuditCache;
use app\service\LockService;
use app\utils\Error;
use app\query\user\service\VisitorService;
use Exception;
use think\facade\Log;

class UserInfoService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserInfoService();
        }
        return self::$instance;
    }

    /**
     * @desc 访客记录
     * @param $userId
     * @param $visitUserId
     * @param $isVisit
     * @return bool
     */
    public function visitRecord($userId, $visitUserId, $isVisit)
    {
        // 访问他人才会记录
        if ($userId == $visitUserId || $isVisit != 1){
            return true;
        }

        // 当前用户为隐身访问不记录
        $isHiddenVisitor = VisitorService::getInstance()->isHiddenVisitor($userId, $visitUserId);
        if ($isHiddenVisitor){
            return true;
        }

        $redis = RedisCommon::getInstance()->getRedis();
        // 我的访客记录
        $visitKey = 'new_visit_user_' . $visitUserId;
        $score = $redis->zScore($visitKey, $userId);
        if (!$score) {
            // 是否新增访客
            $new_visit_num = 'new_visit_num_' . $visitUserId;
            $redis->INCR($new_visit_num);

            // 总访问人数
            $totalCountKey = $this->getNewVisitTotalCountKey($visitUserId);
            if (!$redis->exists($totalCountKey)) {
                $totalCount = $redis->zCard($visitKey);
                $redis->set($totalCountKey, $totalCount);
            }
            $redis->INCR($totalCountKey);
        }

        $time = time();
        $redis->ZADD($visitKey, $time, $userId);

        // 大于1小时计入次数
        $lastTime = $score ?: 0;
        if ($lastTime < $time - 3600) {
            // 今日被访问了多少次
            $timeStr = date('Ymd', $time);
            $toDayVisitedKey = $this->getNewVisitTodayUserKey($visitUserId, $timeStr);
            $redis->INCR($toDayVisitedKey);
            $redis->expire($toDayVisitedKey, 86400 * 2);
            // 当前用户访问了被访问者多少次
            $visitedCountKey = $this->getNewVisitCountKey($userId, $visitUserId);
            $redis->INCR($visitedCountKey);
        }
    }

    /**
     * @desc 今日被访问次数redis key
     * @param $visitUserId
     * @param $timeStr 20220524
     * @return string
     */
    public function getNewVisitTodayUserKey($visitUserId, $timeStr)
    {
        return sprintf('new_visit_today_user_%s_%s', $timeStr, $visitUserId);
    }

    /**
     * @desc 当前用户访问被访问者次数
     * @param $userId
     * @param $visitUserId
     * @return string
     */
    public function getNewVisitCountKey($userId, $visitUserId)
    {
        return sprintf('new_visit_times_%s_%s', $userId, $visitUserId);
    }

    /**
     * @desc 当前用户最大访问人数
     * @param $visitUserId
     * @return string
     */
    public function getNewVisitTotalCountKey($visitUserId)
    {
        return sprintf('new_visit_total_count_%s', $visitUserId);
    }

    /**
     * @desc 今日被访问次数
     * @param $visitUserId
     * @param $timeStr
     * @return int
     */
    public function getNewVisitTodayUserCount($visitUserId, $timeStr)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $toDayVisitedKey = $this->getNewVisitTodayUserKey($visitUserId, $timeStr);
        $visitCount = $redis->get($toDayVisitedKey);

        return (int)$visitCount;
    }


    /**
     * @info 后台头像/昵称/个性签名/背景墙 审核处理
     * @param $id
     * @param $status
     * @param $operatorId
     * @return UserModel|bool|int
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function memberDetailAuditHandler($id, $status, $operatorId)
    {
//        update audit for user
        list($memberDetailAuditModel, $upresult, $userModel) = $this->memberDetailAuditimpl($id, $status, $operatorId);
//        未审核通过发小秘书通知消息
        if ($status !== 1) {
            $typeStr = MemberDetailAuditActionModel::typeToMsg($memberDetailAuditModel->action);
//            $msg = "您的头像/昵称/个性签名/背景墙，未审核通过";
            $msg = sprintf("您的%s，未审核通过", $typeStr);
            YunXinMsg::getInstance()->sendAssistantMsg($memberDetailAuditModel->userId, $msg);
            return $upresult;
        }
//        审核通过后----------
//        action是头像和昵称需要同步网易
//        通知网易云信更新用户名片信息
//        通知房间socket 用户信息变更

        event(new MemberDetailAuditEvent($userModel->userId, $memberDetailAuditModel, $upresult, $userModel, $status, time()));

        return $upresult;
    }


    private function getMemberDetailAuditimplLockKey($id)
    {
        return sprintf("%s_id:%d", "memberDetailAudit_lockKey", $id);
    }

    /**
     * @info update audit for user
     * @param $id
     * @param $status
     * @param $operatorId
     * @return array
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function memberDetailAuditimpl($id, $status, $operatorId)
    {
        if (empty($id)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $lockKey = $this->getMemberDetailAuditimplLockKey($id);
        LockService::getInstance()->lock($lockKey);
        try {
            $memberDetailAuditModel = Sharding::getInstance()->getConnectModel('commonMaster', $id)->transaction(function () use ($id, $status, $operatorId) {
                //        获取该条目信息,检测是否存在，load数据
                $memberDetailAuditModel = MemberDetailAuditDao::getInstance()->loadUserModelWithLock($id);
                if ($memberDetailAuditModel === null) {
                    throw new FQException("审核数据不存在", 500);
                }
                if ($memberDetailAuditModel->status === $status) {
                    throw new FQException("重复审核请检查重试", 9999);
                }
                // update status
                MemberDetailAuditDao::getInstance()->updateStatus($memberDetailAuditModel->id, $status, $operatorId);
                $memberDetailAuditModel->status = $status;
                return $memberDetailAuditModel;
            });

            // reset cache
            MemberDetailAuditCache::getInstance()->clearCache($memberDetailAuditModel->userId, $memberDetailAuditModel->action);

            // 过滤审核不通过状态
            if ($status === 2) {
                //  reset cache
                MemberDetailAuditCache::getInstance()->clearCache($memberDetailAuditModel->userId, $memberDetailAuditModel->action);
                return [$memberDetailAuditModel, true, (object)array()];
            }

            if ($memberDetailAuditModel->action == MemberDetailAuditActionModel::$nickname) {
                Sharding::getInstance()->getConnectModel('commonMaster', $id)->transaction(function () use ($memberDetailAuditModel) {
                    $existsUserId = UserInfoMapDao::getInstance()->getUserIdByNickname($memberDetailAuditModel->content);
                    if ($existsUserId != null && $existsUserId != $memberDetailAuditModel->userId) {
                        throw new FQException('该用户名昵称已存在', 500);
                    }

                    // 清理昵称池相关
                    UserInfoMapDao::getInstance()->updateNickname($memberDetailAuditModel->content, $memberDetailAuditModel->userId);
                    NicknameLibraryDao::getInstance()->updateUseNickName($memberDetailAuditModel->content);
                });
//                SensorsUserService::getInstance()->editUserAttribute($memberDetailAuditModel->userId, ['nickname' => $memberDetailAuditModel->content], 'admin');
            }

            // 审核通过处理
            list($user, $result) = Sharding::getInstance()->getConnectModel('userMaster', $id)->transaction(function () use ($memberDetailAuditModel) {
                // load用户，更新用户数据
                $user = UserRepository::getInstance()->loadUser($memberDetailAuditModel->userId);
                if ($user == null || (int)$user->getUserModel()->cancelStatus != 0) {
                    throw new FQException('用户不存在', 500);
                }

                // 更改用户信息审核成功
                $result = $user->updateAuditProfile($memberDetailAuditModel);
                return [$user, $result];
            });

            // reset cache
            MemberDetailAuditCache::getInstance()->clearCache($memberDetailAuditModel->userId, $memberDetailAuditModel->action);
            Log::info(sprintf('UserInfoService memberDetailAuditimpl userId=%d,MemberDetailAuditModel=%s,status=%d,result=%d', $user->getUserId(), json_encode($memberDetailAuditModel), $status, $result));
            return [$memberDetailAuditModel, $result, $user->getUserModel()];
        } catch (Exception $e) {
            if ($e->getCode() === 9999) {
                throw $e;
            }
//            如操作失败，修改该审核记录为审核失败
            $memberDetailAuditModel = MemberDetailAuditDao::getInstance()->loadData($id);
            if ($memberDetailAuditModel === null) {
                throw new FQException("审核数据不存在", 500);
            }
            MemberDetailAuditDao::getInstance()->updateStatus($memberDetailAuditModel->id, 2, $operatorId);
            Log::error(sprintf('LoginByMobileException memberdetailaudit_id=%d ex=%d:%s', $id, $e->getCode(), $e->getMessage()));
            throw $e;
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
    }

    /**
     * 获取用户资料完成度
     * @throws FQException
     */
    public function getProfileCompleteScale($userId)
    {
        $user = UserRepository::getInstance()->loadUser($userId);
        if (is_null($user)) {
            throw new FQException('用户不存在', 500);
        }
        $allNum = 6;
        $num = 1;
        if (!empty($user->getUserModel()->username)) {
            $num += 1;
        }
        if (!empty($user->getUserModel()->nickname)) {
            $num += 1;
        }
        if (!empty($user->getUserModel()->birthday)) {
            $num += 1;
        }
        if (!empty($user->getUserModel()->city)) {
            $num += 1;
        }
        if (!empty(MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($userId, MemberDetailAuditActionModel::$intro)->content)) {
            $num += 1;
        }
        return sprintf("%.2f", $num / $allNum * 100);
    }

    /**
     * @desc 设置隐藏在线状态
     * @param $userId
     * @param $type // 1:隐藏在线  2:取消隐藏在线
     * @return bool
     */
    public function setHiddenOnline($userId, $type)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getHiddenOnlineKey();
        $userOnline = 1;
        if ($type == 1) {
            $redis->sAdd($redisKey, $userId);
            $userOnline = 3;
        } else if ($type == 2) {
            $redis->sRem($redisKey, $userId);
        }

        // 落库  online  用户在线状态 1在线 2离线 3隐藏在线
        UserModelDao::getInstance()->updateDatas($userId, ['online' => $userOnline]);
        return true;
    }

    /**
     * @desc 隐藏在线状态key
     * @return string
     */
    public function getHiddenOnlineKey()
    {
        return sprintf('user_hidden_online');
    }

    /**
     * @desc 用户是否隐藏在线状态
     * @param $userId
     * @return bool
     */
    public function isHiddenOnline($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getHiddenOnlineKey();
        return $redis->sIsMember($redisKey,$userId);
    }

}




















