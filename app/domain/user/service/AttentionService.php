<?php


namespace app\domain\user\service;


use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\events\FocusFriendDomainEvent;
use app\domain\exceptions\FQException;
use app\domain\forum\dao\ForumBlackModelDao;
use app\domain\user\dao\AttentionModelDao;
use app\domain\user\dao\FansModelDao;
use app\domain\user\dao\FriendModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use app\event\AttentionUserEvent;
use think\facade\Log;

class AttentionService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AttentionService();
        }
        return self::$instance;
    }

    /**
     * 关注
     * @param $userId int 关注人
     * @param $userideds [] 被关注的用户列表
     * @param $isFocus 1关注 2取关
     */
    public function attentionUsers($userId, $userideds, $isFocus)
    {
        $timestamp = time();
        try {
            list($addFansUserIds, $addFriendUserIds, $delFansUserIds, $delFriendUserIds) = $this->attentionUsersImpl($userId, $userideds, $isFocus, $timestamp);

        } catch (FQException $e) {
            if ($e->getCode() === 515) {
                $this->limitFlowFilter($userId);
            }
            Log::error(sprintf('UserService::attentionUsers attentionUsersImpl userId=%d userided=%s', $userId, json_encode($userideds)));
            throw $e;
        }

        foreach ($addFansUserIds as $updateUserId) {
            try {
                $this->attentionedUserImpl($updateUserId, $userId, 1, $timestamp);
            } catch (FQException $e) {
                Log::error(sprintf('UserService::attentionUsers addFans updateUserId=%d userId=%d', $updateUserId, $userId));
            }
        }

        foreach ($delFansUserIds as $updateUserId) {
            try {
                $this->attentionedUserImpl($updateUserId, $userId, 2, $timestamp);
            } catch (FQException $e) {
                Log::error(sprintf('UserService::attentionUsers delFans updateUserId=%d userId=%d', $updateUserId, $userId));
            }
        }

        foreach ($addFriendUserIds as $updateUserId) {
            try {
                $this->attentionedUserImpl($updateUserId, $userId, 3, $timestamp);
            } catch (FQException $e) {
                Log::error(sprintf('UserService::attentionUsers addFriend updateUserId=%d userId=%d', $updateUserId, $userId));
            }
        }

        foreach ($delFriendUserIds as $updateUserId) {
            try {
                $this->attentionedUserImpl($updateUserId, $userId, 4, $timestamp);
            } catch (FQException $e) {
                Log::error(sprintf('UserService::attentionUsers delFriend updateUserId=%d userId=%d', $updateUserId, $userId));
            }
        }
        event(new AttentionUserEvent($userId,$userideds,$timestamp));
    }

    /**
     * 关注 todo check 事务
     * @params  关注人
     * @params  被关注的用户id
     * @return []被关注人需要更新信息
     */
    private function attentionUsersImpl($userId, $userideds, $isFocus, $timestamp)
    {
        try {
            $transModel = Sharding::getInstance()->getConnectModel('userMaster', $userId);

            foreach ($userideds as $userided) {
                if ($userId == $userided) {
                    //自己不能关注自己
                    continue;
                }
                if (!UserModelDao::getInstance()->isUserIdExists($userided)) {
                    throw new FQException('关注的用户不存在', 515);
                }

                if (ForumBlackModelDao::getInstance()->getBlackModel($userId, $userided)) {
                    throw new FQException('你已拉黑对方无法关注 ', 500);
                }

                if (ForumBlackModelDao::getInstance()->getBlackModel($userided, $userId)) {
                    throw new FQException('对方已将你拉黑无法关注 ', 500);
                }
            }

            return $transModel->transaction(function() use($userId, $userideds, $isFocus, $timestamp){
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $addFansUserIds = []; # 添加粉丝的用户
                $addFriendUserIds = []; # 添加好友的用户
                $delFansUserIds = []; # 删除粉丝关系的用户
                $delFriendUserIds = []; # 删除好友关系的用户
                foreach ($userideds as $userided) {
                    $model = AttentionModelDao::getInstance()->loadAttention($userId, $userided);
                    if ($isFocus == 1) {
                        if ($model != null) {
                            //已关注过此用户
                            continue;
                        }

                        AttentionModelDao::getInstance()->addAttention($userId, $userided, $timestamp);
                        $addFansUserIds[] = $userided;

                        // 是否相关关注
                        if (AttentionModelDao::getInstance()->loadAttention($userided, $userId)) {
                            FriendModelDao::getInstance()->addFriend($userId, $userided, $timestamp);
                            $addFriendUserIds[] = $userided;
                        }

                        event(new FocusFriendDomainEvent($user, $userided, $isFocus, time()));
                    } else {
                        if (empty($model)) {
                            //没有关注过 不需要取关
                            continue;
                        }

                        AttentionModelDao::getInstance()->delAttention($userId, $userided);
                        $delFansUserIds[] = $userided;

                        # 相互关注状态取消
                        if (FriendModelDao::getInstance()->loadFriendModel($userId, $userided)) {
                            FriendModelDao::getInstance()->delFriend($userId, $userided);
                            $delFriendUserIds[] = $userided;
                        }
                        event(new FocusFriendDomainEvent($user, $userided, $isFocus, time()));
                    }
                }
                return [$addFansUserIds, $addFriendUserIds, $delFansUserIds, $delFriendUserIds];
            });
        } catch (\Exception $e) {
            Log::error(sprintf('attentionUsersImpl userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * $userId 需要更新信息的人 如：被关注的人
     * $fromUserId 触发更新的人 如：关注的人
     * $type 1-添加粉丝关系 2-删除粉丝关系 3-添加好友关系 4-删除好友关系
     * 被关注人需要更新信息
     */
    private function attentionedUserImpl($userId, $fromUserId, $type, $time)
    {
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $fromUserId, $type, $time){
                UserRepository::getInstance()->loadUser($userId);
                if ($type == 1){
                    FansModelDao::getInstance()->addFans($userId, $fromUserId, $time);
                }elseif ($type == 2){
                    FansModelDao::getInstance()->delFans($userId, $fromUserId);
                }elseif ($type == 3){
                    FriendModelDao::getInstance()->addFriend($userId, $fromUserId, $time);
                }elseif ($type == 4){
                    FriendModelDao::getInstance()->delFriend($userId, $fromUserId);
                }
            });
        } catch (\Exception $e) {
            Log::error(sprintf('attentionedUserImpl userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * @info 第二次就封号
     */
    private function limitFlowFilter($userId)
    {
        $cacheKey = sprintf("attentionUsers_limitFlowFilter_userid:%s", $userId);
        $rules = [
            60 => 50,
            864000 => 72000,
        ];
        $server = new \app\common\server\LimitFlow($cacheKey, $rules);
        if ($server->isPass()) {
//            封号
            $admin_id = 0;
            $desc = "系统封禁，非法操作";
            $unixTime = time();
            $blackRe = MemberBlackService::getInstance()->memberBlacks($userId, $unixTime, $desc, $admin_id);
            Log::info(sprintf('UserService::limitFlowFilter userId=%d blackRe=%d', $userId, $blackRe));
            throw new FQException('操作频繁，请稍后再试', 500);
        }
    }

    /**
     * @desc 获取备注同时维护云信好友关系
     * @param $userId
     * @param $toUserid
     * @return \app\domain\user\model\AttentionModel|null
     */
    public function loadAttentionHandleFriend($userId, $toUserid)
    {
        $attention = AttentionModelDao::getInstance()->loadAttention($userId, $toUserid);
        if ($attention) {
            try {
                // 云信添加好友关系--支持幂等
                YunxinCommon::getInstance()->addFriend($userId, $toUserid);
            } catch (Exception $e) {
                Log::error(sprintf('AttentionService::loadAttentionHandleFriend Yunxin addFriend $userId=%d ex=%d:%s file=%s:%d',
                    $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            }
        }
        return $attention;
    }

    /**
     * @desc 设置用户备注
     * @param $userId
     * @param $toUserid
     * @param $remarkName
     * @return bool
     */
    public function setUserRemark($userId, $toUserid, $remarkName)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = $this->getUserRemarkKey($userId,$toUserid);

        // 如果备注为空，删除备注
        if (!$remarkName){
            $redis->del($redisKey);
        } else {
            $redis->set($redisKey,$remarkName);
        }
        // 同时云信设置好友备注
        try {
            YunxinCommon::getInstance()->updateFriend($userId, $toUserid, $remarkName);
        } catch (Exception $e) {
            Log::error(sprintf('AttentionService::setUserRemark Yunxin updateFriend $userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
        return true;
    }

    /**
     * @desc 用户备注的key
     * @param $userId
     * @param $toUserid
     * @return string
     */
    public function getUserRemarkKey($userId, $toUserid)
    {
        return sprintf('user_remark_name_%s_%s', $userId, $toUserid);
    }

}