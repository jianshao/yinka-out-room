<?php


namespace app\query\user;


use app\domain\guild\cache\CachePrefix;
use app\domain\user\model\UserModel;
use app\event\VisitUserInfoEvent;
use app\query\room\cache\RedisCommon;
use app\query\user\cache\QueryUserCache;
use app\query\user\cache\UserModelCache;
use app\query\user\dao\UserInfoMapDao;
use app\query\user\elastic\UserModelElasticDao;
use app\service\CommonCacheService;
use app\service\LockService;
use think\facade\Log;

class QueryUserService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new QueryUserService();
        }
        return self::$instance;
    }

    //提审中
    public function searchVersionUsers($search)
    {

        $ret = [];
        $userId = UserInfoMapDao::getInstance()->getUserIdByPrettyId(intval($search));
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if (!empty($userModel)) {
            $ret[] = $userModel;
        }

        return $ret;

    }

    /**
     * @Info 搜索用户 last 搜用户id、靓号
     * @return UserModel
     */
    public function searchUser($userId)
    {
        $userId = UserInfoMapDao::getInstance()->getUserIdByPrettyId(intval($userId));
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if (!empty($userModel)) {
            return $userModel;
        }
        return null;
    }


    /**
     * @param $uids
     * @return array
     */
    public function loadUserForUids($uids, $limit = 5)
    {
        $result = [];
        if (empty($uids)) {
            return $result;
        }
        $mark = 0;
        $redis_connect = RedisCommon::getInstance()->getRedis();
        foreach ($uids as $uid) {
            if ($mark >= $limit) {
                break;
            }
            $userModel = UserModelCache::getInstance()->getUserInfo($uid);
            if ($userModel === null) {
                continue;
            }
            $current_room_id = CommonCacheService::getInstance()->getUserCurrentRoom($uid);
            if ($current_room_id === 0) {
                continue;
            }
            $isLock = $redis_connect->sIsMember(CachePrefix::$roomLock, $current_room_id);
            if ($isLock === true) {
                continue;
            }
            $result[] = $userModel;
            $mark++;
        }
        return $result;

    }

    /**
     * @info 首页搜索用户的逻辑调整 搜用户id、靓号 或者是搜昵称（模糊搜）
     * @param $search
     * @param $offset
     * @param $count
     * @return array
     */
    public function searchUsersForElasticDb($search, $offset, $count)
    {
        if (is_numeric($search)) {
            list($userModels, $total) = UserModelElasticDao::getInstance()->searchUserForId($search, $offset, $count);
        } else {
            list($userModels, $total) = UserModelElasticDao::getInstance()->searchUserForNickname($search, $offset, $count);
        }
        return [$userModels, $total];
    }

    /**
     * @info 首页搜索用户的逻辑调整 搜用户id、靓号 或者是搜昵称（模糊搜）
     * @param $search
     * @param $offset
     * @param $count
     * @return array
     */
    public function searchUsersForElastic($search, $offset, $count)
    {
        if (empty($search)) {
            return [[], 0];
        }
//        $cacheData = QueryUserCache::getInstance()->getSearchUserForElasticCache($search);
//        if ($cacheData !== false) {
//            return [$cacheData, count($cacheData)];
//        }
//        $lockKey = QueryUserCache::getInstance()->getSearchUserForElasticLockKey($search);
//        LockService::getInstance()->lock($lockKey);
//        try {
//            list($list, $total) = $this->searchUsersForElasticDb($search, $offset, $count);
////            搜索权重sort
//            if (empty($list)) {
//                QueryUserCache::getInstance()->searchUserForElasticStoreZero($search);
//            } else {
//                QueryUserCache::getInstance()->searchUserForElasticStoreModel($search, $list);
//            }
//        } finally {
//            LockService::getInstance()->unlock($lockKey);
//        }
        list($list, $total) = $this->searchUsersForElasticDb($search, $offset, $count);

        return [$list, $total];
    }


    /**
     * @Info 搜索用户 last 搜用户id、靓号、昵称（模糊搜）
     * @param $search
     * @param $offset
     * @param $count
     * @param null $excludeUserIds
     * @return array
     */
    public function searchUsers($search, $offset, $count, $excludeUserIds = null)
    {
        $count = $count == 0 ? 50 : $count;
        $excludeUserIds = empty($excludeUserIds) ? [] : $excludeUserIds;
        list($userIds, $total) = UserInfoMapDao::getInstance()->dimSearchByNickname($search, $offset, $count);
        foreach ($userIds as $key => $userId) {
            if (in_array($userId, $excludeUserIds)) {
                unset($userId);
            }
        }
        $userModels = UserModelCache::getInstance()->findList($userIds);

        if (is_numeric($search)) {
            $userId = UserInfoMapDao::getInstance()->getUserIdByPrettyId(intval($search));
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            if (!empty($userModel) && !in_array($userModel->userId, $excludeUserIds)) {
                $userModels[] = $userModel;
                $total += 1;
            }
        }

        return [$userModels, $total];
    }


    /**
     * 全匹配 只搜用户id、靓号、昵称
     * @param $search
     * @param null $excludeUserIds
     * @return array
     */
    public function matchUsers($search, $excludeUserIds = null)
    {
        $ret = [];
        $excludeUserIds = empty($excludeUserIds) ? [] : $excludeUserIds;
        if (is_numeric($search)) {
            $userId = UserInfoMapDao::getInstance()->getUserIdByPrettyId(intval($search));
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            if (!empty($userModel) && !in_array($userModel->userId, $excludeUserIds)) {
                $ret[] = $userModel;
            }

            return [$ret, count($ret)];
        } else {
            $userId = UserInfoMapDao::getInstance()->getUserIdByNickname($search);
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            if (!empty($userModel) && !in_array($userModel->userId, $excludeUserIds)) {
                $ret[] = $userModel;
            }

            return [$ret, count($ret)];
        }
    }

    public function queryUserInfo($userId, $queryUserId, $isVisit)
    {
        $userModel = UserModelCache::getInstance()->getUserInfo($queryUserId);
        if ($userModel != null) {
            Log::info(sprintf('QueryUserInfo userId=%d queryUserId=%d isVisit=%d',
                $userId, $queryUserId, $isVisit));
            event(new VisitUserInfoEvent($userId, $queryUserId, $isVisit, time()));
        }
        return $userModel;
    }
}