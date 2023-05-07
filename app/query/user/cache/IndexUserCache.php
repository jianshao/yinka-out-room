<?php

namespace app\query\user\cache;

use app\common\CacheRedis;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;

//首页用户缓存
class IndexUserCache
{
    protected static $instance;
    private $redis;

    private $page;
    private $pageNum;
    private $sex;
    private $userId;

    public $sexCoveType = [
        'man' => "1",
        'woman' => "2",
        'all' => 'all',
    ];


    public function __construct($page = null, $pageNum = null, $sex = null, $userId = null)
    {
        $this->redis = CacheRedis::getInstance()->getRedis();
        $this->page = is_null($page) ? null : $page;
        $this->pageNum = is_null($pageNum) ? null : $pageNum;
        $this->sex = is_null($sex) ? null : $sex;
        $this->userId = is_null($userId) ? null : $userId;
    }

    private function getPublicBuckedSexKey()
    {
        return sprintf("%s:%s", CachePrefix::$publicBucketBoxSex, $this->sex);
    }

    private function getPrivateUserBucketKey()
    {
        return sprintf("%suid:%s:sex:%s", CachePrefix::$privateUserBucketPrefix, $this->userId, $this->sex);
    }

    private function getPrivateOfflineUserBucketKey()
    {
        return sprintf("%suid:%s:sex:%s", CachePrefix::$privateOfflineUserBucketPrefix, $this->userId, $this->sex);
    }

//    初始化私有在线用户缓存桶
    public function initPrivateUserBucket($userId)
    {
        $privateKey = $this->getPrivateUserBucketKey();
//        检查是否存在桶，是否过期
        if ($this->redis->EXISTS($privateKey)) {
            return true;
        }
//        初始化
        $pubilcKey = $this->getPublicBuckedSexKey();
        $publicData = $this->redis->lRange($pubilcKey, 0, -1);
        foreach ($publicData as $uid) {
            if ($uid == $userId) {
                continue;
            }
            $this->redis->rPush($privateKey, $uid);
        }
        $this->redis->expire($privateKey, 180);
        return true;
    }

    /**
     * @info 返回到offline桶
     * @param $offlineUser
     */
    public function rpushOfflineBucket($offlineUid)
    {
        if (empty($offlineUid)) {
            return true;
        }
        $offlineBucketKey = $this->getPrivateOfflineUserBucketKey();
        foreach ($offlineUid as $uid) {
            $this->redis->rPush($offlineBucketKey, $uid);
        }
        $this->redis->expire($offlineBucketKey, 1800);
    }

    public function getOnlinePrivateUser()
    {
        $privateKey = $this->getPrivateUserBucketKey();
        $data = [];
        for ($i = 0; $i < $this->pageNum; $i++) {
            $item = $this->redis->lpop($privateKey);
            if (empty($item)) {
                break;
            }
            $data[] = $item;
        }
        return $data;
    }

    public function find($uid)
    {
        if (empty($uid)) {
            throw new FQException("cache User uid empty");
        }
    }

    private function countOfflineUserNum()
    {
        $privateOfflineKey = $this->getPrivateOfflineUserBucketKey();//离线用户列表
        return $this->redis->lLen($privateOfflineKey);
    }


    /**
     * @param $pageNum
     * @return bool
     */
    public function filterOfflineUserNum($pageNum)
    {
        if (empty($pageNum)) {
            $pageNum = 10;
        }
//        count离线用户列表数量
//        超过10个不在线用户则刷新用户私池
        $number = $this->countOfflineUserNum();
        $privateKey = $this->getPrivateUserBucketKey();
        if ($number >= 10) {
            $this->redis->del($privateKey);
            $this->clearOfflineUserNum();
            return true;
        }
//        剩余数量不足一屏则刷新用户私池
        $privateKey = $this->getPrivateUserBucketKey();
        $onlineBucketNumber = $this->redis->lLen($privateKey);
        if ($onlineBucketNumber < $pageNum) {
            $this->redis->del($privateKey);
            $this->clearOfflineUserNum();
            return true;
        }
        return true;
    }

    private function clearOfflineUserNum()
    {
        $privateOfflineKey = $this->getPrivateOfflineUserBucketKey();//离线用户列表
        $this->redis->del($privateOfflineKey);
    }


    /**
     * @info 从用户登陆的zset缓存中获取指定时间的离线用户
     * @param $limit
     * @param $time
     * @return array
     */
    public function getOffLineUserIdsForCache($limit, $startTime, $sex)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getUserHistoryKeyForSex($sex);
        $endTime = $startTime - 86400;
        $ids = $redis->zRevRangeByScore($cacheKey, $startTime, $endTime);
        if (empty($ids)) {
            return [];
        }
        return array_slice($ids, 0, $limit);
    }

    private function getUserHistoryKeyForSex($sex)
    {
        $sexCoveData = [
            'man' => "1",
            'woman' => "2",
            'all' => 'all',
        ];
        $cacheSex = $sexCoveData[$sex];
        return sprintf('user_online_history_%s_list', $cacheSex);
    }

}


