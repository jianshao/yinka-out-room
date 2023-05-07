<?php

namespace app\domain\room\dao;

use app\common\RedisCommon;
use app\domain\guild\cache\CachePrefix;
use app\query\room\cache\GuildQueryRoomModelCache;

//娱乐页房间人气值dao
class RecreationHotModelDao
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RecreationHotModelDao();
        }
        return self::$instance;
    }

    public function getHotValueAll($roomId)
    {
        $redis_connnect = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
        return $redis_connnect->hGetAll($cacheKey);
    }

    public function getRoomHotValue($roomId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
        $data = $redis->hGetAll($cacheKey);
        if (empty($data)) {
            return 0;
        }

        $value = 0;
        if (isset($data['orignal'])) {
            $value += $data['orignal'];
        }
        if (isset($data['gift'])) {
            $value += $data['gift'];
        }
        return intval($value);
    }


    public function setFieldValue($roomId, $field, $value)
    {
        $redis_connnect = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
        if ($value < 0) {
            $value = 0;
        }
        return $redis_connnect->hSet($cacheKey, $field, $value);
    }

    /**
     * @info  全麦时增加人气值
     * @param $roomId
     * @param $isGuild
     * @param $value
     * @return int
     */
    public function incGiftHotForWholewheat($roomId, $isGuild, $pointValue)
    {
        if (empty($roomId) || empty($pointValue)) {
            return 0;
        }
        $value = $pointValue * 100;
        return $this->incGiftGuildHotValue($roomId, $value);
    }

    /**
     * @info  增加送礼热度value
     * @param $roomId
     * @param $isGuild
     * @param $value
     * @return bool|int
     */
    public function incGiftHotValue($roomId, $isGuild, $value)
    {
        return $this->incGiftGuildHotValue($roomId, $value);
    }

    public function setOrignalHotValue($roomId, $value)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
        return $redis->hSet($cacheKey, 'orignal', $value);
    }

    /**
     * @param $roomId
     * @param $value
     * @return int
     */
    public function incGiftGuildHotValue($roomId, $value)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
        return $redis->Hincrby($cacheKey, 'gift', $value);
    }

    private function getGuildHotKey($roomId)
    {
        return sprintf("%s:%s", CachePrefix::$RecreationRoomHot, $roomId);
    }


    public function incGiftUserHotValue($roomId, $value)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
        $redu = $redis->hSet($cacheKey, 'gift', $value);
        return $redu ? $redu : 0;
    }

    public function lockRoom($roomId)
    {
        GuildQueryRoomModelCache::getInstance()->lockRoom($roomId);
    }

}