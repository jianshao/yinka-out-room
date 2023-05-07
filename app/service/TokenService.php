<?php

namespace app\service;

use app\common\RedisCommon;
use think\facade\Log;

class TokenService
{
    protected static $instance;
    private $tokenExpiresTime = "864000";
    private $tokenSalt = "hello_world";

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TokenService();
        }
        return self::$instance;
    }

    public function getUserIdByToken($token) {
        $userId = RedisCommon::getInstance()->getRedis()->get($token);
        Log::info(sprintf('GetUserIdByToken token=[%s] userId=[%s]', $token, $userId));
        if (empty($userId)) {
            return 0;
        }
        return intval($userId);
    }

    public function getTokenByUserId($userId) {
        $token = RedisCommon::getInstance()->getRedis()->get($userId);
        Log::info(sprintf('GetTokenByUserId userId=[%d] token=[%s]', $userId, $token));
        return $token;
    }

    public function setToken($userId, $token) {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->SETEX($userId, $this->tokenExpiresTime, $token);
        $redis->SETEX($token, $this->tokenExpiresTime, $userId);
    }

    public function resetToken($userId) {
        $this->removeToken($userId);
        $token = TokenService::getInstance()->genToken($userId);
        TokenService::getInstance()->setToken($userId, $token);
        return $token;
    }

    public function refreshToken($userId) {
        $token = $this->getTokenByUserId($userId);
        if ($token) {
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->SETEX($userId, $this->tokenExpiresTime, $token);
            $redis->SETEX($token, $this->tokenExpiresTime, $userId);
        }
        return $token;
    }

    public function removeToken($userId) {
        $oldToken = $this->getTokenByUserId($userId);
        if ($oldToken) {
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->del($oldToken);
            $redis->del($userId);
        }
    }

    public function genToken($userId) {
        return generateToken($this->tokenSalt);
    }
}