<?php

namespace app\service;

use app\common\RedisCommon;
use think\facade\Log;

//wechat login withdraw token service
class WithdrawTokenService
{
    protected static $instance;
    private $tokenExpiresTime = "86400";
    private $tokenSalt = "hello_world";

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new WithdrawTokenService();
        }
        return self::$instance;
    }

    private function getRedisObject()
    {
        return RedisCommon::getInstance()->getRedis(['select' => 15]);
    }

    public function getUserIdByToken($token)
    {
        $userId = $this->getRedisObject()->get($token);
        Log::info(sprintf('GetUserIdByToken token=[%s] userId=[%s]', $token, $userId));
        if (empty($userId)) {
            return 0;
        }
        return intval($userId);
    }

    public function getTokenByUserId($userId)
    {
        $token = $this->getRedisObject()->get($userId);
        Log::info(sprintf('GetTokenByUserId userId=[%d] token=[%s]', $userId, $token));
        return $token;
    }

    public function setToken($userId, $token) {
        $redis = $this->getRedisObject();
        $redis->SETEX($userId, $this->tokenExpiresTime, $token);
        $redis->SETEX($token, $this->tokenExpiresTime, $userId);
    }

    public function resetToken($userId)
    {
        $this->removeToken($userId);
        $token = $this->genToken($userId);
        $this->setToken($userId, $token);
        return $token;
    }

    public function refreshToken($userId) {
        $token = $this->getTokenByUserId($userId);
        if ($token) {
            $redis = $this->getRedisObject();
            $redis->SETEX($userId, $this->tokenExpiresTime, $token);
            $redis->SETEX($token, $this->tokenExpiresTime, $userId);
        }
        return $token;
    }

    public function removeToken($userId) {
        $oldToken = $this->getTokenByUserId($userId);
        if ($oldToken) {
            $redis = $this->getRedisObject();
            $redis->del($oldToken);
            $redis->del($userId);
        }
    }

    public function genToken($userId) {
        return generateToken($this->tokenSalt);
    }
}