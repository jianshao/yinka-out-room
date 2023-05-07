<?php


namespace app\domain\activity\christmas;


use app\common\RedisCommon;
use think\facade\Log;

class ChristmasUserDao
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ChristmasUserDao();
        }
        return self::$instance;
    }

    public function buildKey() {
        return 'christmas_user';
    }

    public function incrLindDang($userId, $count) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        Log::debug(sprintf('ChristmasUserDao::incrLindDang userId=%d count=%d',
            $userId, $count));
        return $redis->hIncrBy($key, $userId, $count);
    }

    public function getLindDang($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        return (int)$redis->hget($key, $userId);
    }
}