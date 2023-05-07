<?php


namespace app\domain\activity\zhongqiuPK;


use app\common\RedisCommon;
use think\facade\Log;

class ZhongQiuPKUserDao
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ZhongQiuPKUserDao();
        }
        return self::$instance;
    }

    public function buildKey() {
        return 'zhongqiupk_user';
    }

    public function loadUser($userId, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        $jstr = $redis->hget($key, $userId);

        $ret = null;
        if (!empty($jstr)) {
            try {
                $jsonObj = json_decode($jstr, true);
                $ret = new ZhongQiuPKUser($userId);
                $ret->fromJson($jsonObj, $timestamp);
            } catch (\Exception $e) {
                Log::error(sprintf('ZhongQiuPKUserDao loadUser BadData userId=%d data=%s trace=%s',
                    $userId, $jstr, $e->getTraceAsString()));
            }
        }

        if ($ret == null) {
            $ret = new ZhongQiuPKUser($userId, $timestamp);
        }

        Log::debug(sprintf('ZhongQiuPKUserDao::loadUser userId=%d data=%s ret=%s',
            $userId, $jstr, json_encode($ret->toJson())));

        return $ret;
    }

    public function removeUser($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        $redis->hDel($key, $userId);
    }

    public function saveUser($user) {
        $jstr = json_encode($user->toJson());
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        $redis->hSet($key, $user->userId, $jstr);

        Log::debug(sprintf('ZhongQiuPKUserDao::saveUser userId=%d data=%s',
            $user->userId, $jstr));
    }
}