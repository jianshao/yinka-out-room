<?php

namespace app\query\user\cache;

use app\common\RedisCommon;

class UserIdCache
{

    protected static $instance;
    protected $redis = null;
    private $usedKey = "used.global.newuserid"; # 当前用过的userId的key
    private $makeUserKey = "global.newuserid";      # 生成新的userIds的key

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserIdCache();
            self::$instance->redis = RedisCommon::getInstance()->getRedis(['select' => 12]);
        }
        return self::$instance;
    }


    public function getUserId() {
        return (int)$this->redis->sPop($this->makeUserKey);
    }

    public function saveUserIds($ids) {
        $this->redis->sAdd($this->makeUserKey, ...$ids);
    }

    public function getRemainUserIdsCount() {
        return (int)$this->redis->sCard($this->makeUserKey);
    }

    public function getUsedUserId() {
        return (int)$this->redis->get($this->usedKey);
    }

    public function saveUsedUserId($userId) {
        $this->redis->set($this->usedKey, $userId);
    }
}