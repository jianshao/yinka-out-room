<?php

namespace app\service;

use app\common\RedisLock;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use Exception;

class LockService
{
    protected static $instance;
    // map<lockKey, [RedisLock, LockRes, lockCount]>
    private $lockMap = [];
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LockService();
        }
        return self::$instance;
    }

    public function lock($key, $timeout=3000) {
        $lock = ArrayUtil::safeGet($this->lockMap, $key);
        if ($lock != null) {
            $lock[2] += 1;
        } else {
            $redisService = [[config('config.redis.host'), config('config.redis.port'), 0.1]];
            $redisLock = new RedisLock($redisService, 200, 15);
            $lockRes = $redisLock->lock($key, $timeout);
            if (!$lockRes) {
                throw new FQException('操作频繁', 500);
            }
            $this->lockMap[$key] = [$redisLock, $lockRes, 1];
        }
    }

    public function unlock($key) {
        $lock = ArrayUtil::safeGet($this->lockMap, $key);
        if ($lock != null) {
            $lock[2] -= 1;
            if ($lock[2] <= 0) {
                $lock[0]->unlock($lock[1]);
                unset($this->lockMap[$key]);
            }
        }
    }
}