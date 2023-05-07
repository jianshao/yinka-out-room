<?php


namespace app\query\notice\cache;


use app\common\RedisCommon;

class NoticeCache
{
    protected static $instance;
    protected $redis = null;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new NoticeCache();
            self::$instance->redis = RedisCommon::getInstance()->getRedis();
        }
        return self::$instance;
    }

    public function getLastNoticeTime($userId){
        $time = $this->redis->HGET('notice_msg_uid', $userId);
        return $time ?: 0;
    }

    public function updateNoticeTime($userId, $timestamp){
        $this->redis->hset('notice_msg_uid', $userId, $timestamp);
    }
}