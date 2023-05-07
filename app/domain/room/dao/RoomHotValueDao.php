<?php

namespace app\domain\room\dao;

use app\domain\guild\cache\CachePrefix;
use app\query\room\cache\GuildQueryRoomModelCache;
use app\query\room\cache\RedisCommon;

class RoomHotValueDao
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomHotValueDao();
        }
        return self::$instance;
    }

    public function getHotValueAll($roomId)
    {
        $redis_connnect = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
        return $redis_connnect->hGetAll($cacheKey);
    }

    public function getRoomHotValue($roomId){
        $redis = RedisCommon::getInstance()->getRedis($roomId);
        $cacheKey = $this->getGuildHotKey($roomId);
        $data = $redis->hGetAll($cacheKey);
        if (empty($data)){
            return 0;
        }

        $value = 0;
        if (isset($data['member'])) {
            $value += $data['member'];
        }
        if (isset($data['orignal'])) {
            $value += $data['orignal'];
        }
        if (isset($data['gift'])) {
            $value += $data['gift'];
        }
        if (isset($data['chat'])) {
            $value += $data['chat'];
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

    public function incGiftGuildHotValue($roomId, $value)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $cacheKey = $this->getGuildHotKey($roomId);
//        Log::info(sprintf('incGiftHotGetValue cachekey cachekey=%s', $cacheKey));
        return $redis->Hincrby($cacheKey, 'gift', $value);
    }

    private function getGuildHotKey($roomId)
    {
        return sprintf("%s:%s", CachePrefix::$guildRoomHot, $roomId);
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

    public function unlockRoom($roomId)
    {
        GuildQueryRoomModelCache::getInstance()->unlockRoom($roomId);
    }
}