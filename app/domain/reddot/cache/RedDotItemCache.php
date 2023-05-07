<?php

namespace app\domain\reddot\cache;

use app\common\CacheRedis;
use app\utils\Arithmetic;

//红点缓存操作类 prefix  红点数量操作类
class RedDotItemCache
{

    protected $pk = 'id';
    protected static $instance;


    public function __construct(array $data = [])
    {
        $this->redis = CacheRedis::getInstance()->getRedis();
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RedDotItemCache();
        }
        return self::$instance;
    }

    private function getCacheKey($userId,$field)
    {
        return sprintf("%s%s:%s", CachePrefix::$redHotForUserId, $userId,$field);
    }

    /**
     * @param $userId
     * @param $field
     * @param int $number
     * @return int
     */
    public function incr($userId, $field, $number = 1,$hashKey="")
    {
        if (empty($userId) || empty($field)) {
            return 0;
        }
        $cacheKey = $this->getCacheKey($userId,$field);
        $re = $this->redis->hIncrBy($cacheKey, $hashKey, $number);
        $this->redis->expire($cacheKey, CachePrefix::$expireTime);
        return $re;
    }

    /**
     * @param $userId
     * @param $field
     * @param int $number
     * @return int
     */
    public function hset($userId, $field, $number = 1,$hashKey="")
    {
        if (empty($userId) || empty($field)) {
            return 0;
        }
        $cacheKey = $this->getCacheKey($userId,$field);
        $re = $this->redis->hSet($cacheKey, $hashKey, $number);
        $this->redis->expire($cacheKey, CachePrefix::$expireTime);
        return $re;
    }

    /**
     * @param $userId
     * @param $field
     * @param string $hashKey
     * @return false|int|string
     */
    public function hget($userId, $field,$hashKey="")
    {
        if (empty($userId) || empty($field)) {
            return "";
        }
        $cacheKey = $this->getCacheKey($userId,$field);
        $re = $this->redis->hGet($cacheKey, $hashKey);
        return $re;
    }


    /**
     * @param $userId
     * @param $field
     * @return array
     */
    public function hgetAll($userId, $field)
    {
        if (empty($userId) || empty($field)) {
            return [];
        }
        $cacheKey = $this->getCacheKey($userId,$field);
        $re = $this->redis->hGetAll($cacheKey);
        return $re;
    }

    public function find($userId,$field)
    {
        if (empty($userId)) {
            return 0;
        }
        $cacheKey = $this->getCacheKey($userId,$field);
        $data = $this->redis->hGetAll($cacheKey);
        if (empty($data)) {
            return [];
        }
        return $data;
    }



    /**
     * @param $userId
     * @param $field
     * @param int $number
     * @return int
     */
    public function decr($userId, $field, $number = -1,$hashKey="")
    {
        if (empty($userId) || empty($field)) {
            return 0;
        }
        $cacheKey = $this->getCacheKey($userId,$field);
        $oldNumber = $this->redis->hGet($cacheKey, $hashKey);
        if ($oldNumber <= 0) {
            return 0;
        }
//        边界保护
        if (Arithmetic::add($oldNumber,$number)<=0){
            $number=Arithmetic::negateNumber($oldNumber);
        }
        $re = $this->redis->hIncrBy($cacheKey, $hashKey, $number);
        $this->redis->expire($cacheKey, CachePrefix::$expireTime);
        return $re;
    }


}


