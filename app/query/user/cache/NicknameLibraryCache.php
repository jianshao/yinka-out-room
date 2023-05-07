<?php

namespace app\query\user\cache;

use app\api\view\v1\UserView;
use app\common\CacheRedis;
use app\domain\user\dao\AvatarLibraryDao;
use app\domain\user\dao\NicknameLibraryDao;

//用户缓存
class NicknameLibraryCache
{
    protected static $instance;
    private $redis;

    public function __construct(array $data = [])
    {
        $this->redis = CacheRedis::getInstance()->getRedis();
    }

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new NicknameLibraryCache();
        }
        return self::$instance;
    }

    private function getCacheKey()
    {
        return CachePrefix::$nicknameCache;
    }

    private function getLockKey()
    {
        return CachePrefix::$lockCacheKey;
    }

    /**
     * @return array|bool|mixed|string
     */
    public function getNickName()
    {
        $cacheKey = $this->getCacheKey();
//        pop数据，
        $item = $this->redis->sPop($cacheKey);
//        存在则返回
        if (!empty($item)) {
            return $item;
        }
        $lockKey = $this->getLockKey();
        $number = $this->redis->incr($lockKey);
        if ($number > 1) {
            return "";
        }
        $this->redis->expire($lockKey, 3);
//        没有则从db取，并且存入缓存
        $nicknameList = NicknameLibraryDao::getInstance()->getNotUsedNicknameList();
        $this->redis->sAdd($cacheKey, ...$nicknameList);
        $this->redis->expire($cacheKey, 600);
//        pop一条数据
        $item = $this->redis->sPop($cacheKey);
        if (empty($item)) {
            return "";
        }
        return $item;
    }


    public function getRandManAvatar()
    {
        $models = AvatarLibraryDao::getInstance()->getManList();
        shuffle($models);
        $itemModel = array_pop($models);
        return UserView::randAvatar($itemModel);
    }

    public function getRandWomanAvatar()
    {
        $models = AvatarLibraryDao::getInstance()->getWomanList();
        shuffle($models);
        $itemModel = array_pop($models);
        return UserView::randAvatar($itemModel);
    }

}


