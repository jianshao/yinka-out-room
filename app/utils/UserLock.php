<?php

namespace app\utils;

use app\common\RedisLock;
use Exception;

class UserLock
{
    private $key = '';
    private $lock = '';
    private $context = '';

    private function __construct($key, $lock, $context) {
        $this->key = $key;
        $this->lock = $lock;
        $this->context = $context;
    }

    public static function tryLockNoException($userId, $timeout=3000) {
        $redisService = [[config('config.redis.host'),config('config.redis.port'), 0.1]];
        $lock = new RedisLock($redisService);
        $key = 'redis_user_lock_' . $userId;
        $context = $lock->lock($key, $timeout);
        if (!$context) {
            return null;
        }
        return new UserLock($key, $lock, $context);
    }

    public static function tryLock($userId, $timeout=3000) {
        $ret = UserLock::tryLockNoException($userId, $timeout);
        if ($ret == null) {
            throw new Exception('操作过快,请重试', 500);
        }
        return $ret;
    }

    public function unlock() {
        $this->lock->unlock($this->context);
    }
}


