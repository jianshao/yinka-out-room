<?php


namespace app\domain\user\service;


use app\common\RedisCommon;

//线上测试
class OnlineTestService
{
    protected static $instance;
    private $user_key = 'online_test_users';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new OnlineTestService();
        }
        return self::$instance;
    }

    public function getOnlineTestUser() {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->SMEMBERS($this->user_key);
    }



}