<?php


namespace app\domain\activity\qixi;


use app\common\RedisCommon;
use think\facade\Log;

class QixiUserDao
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new QixiUserDao();
        }
        return self::$instance;
    }

    public function buildKey($userId) {
        return 'qixi_user:'.$userId;
    }

    public function loadQixiUser($userId, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId);
        $jstr = $redis->hgetall($key);

        $ret = null;
        if (!empty($jstr)) {
            try {
//                $jsonObj = json_decode($jstr, true);
                $ret = new QixiUser($userId);
                $ret->fromJson($jstr, $timestamp);
            } catch (\Exception $e) {
                Log::error(sprintf('QixiUserDao loadQixiUser BadData userId=%d data=%s trace=%s',
                    $userId, json_encode($jstr), $e->getTraceAsString()));
            }
        }

        if ($ret == null) {
            $ret = new QixiUser($userId, $timestamp);
        }

        Log::debug(sprintf('QixiUserDao::loadQixiUser userId=%d data=%s ret=%s',
            $userId, json_encode($jstr), json_encode($ret->toJson())));

        return $ret;
    }

    public function removeQixiUser($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId);
        $redis->hDel($key, $userId);
    }

    public function incrMissingValue($userId, $value) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId);
        $redis->hIncrBy($key, 'missingValue', $value);

        Log::debug(sprintf('QixiUserDao::addMissingValue userId=%d value=%d',
            $userId, $value));
    }

    public function getMissingValue($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId);
        return intval($redis->hget($key, 'missingValue'));
    }

    public function getCP($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId);
        return intval($redis->hget($key, 'cpUserId'));
    }

    public function hMGetUser($userId, $data) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId);
        return $redis->hMGet($key, $data);
    }

    public function hMSetUser($userId, $data) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($userId);
        $redis->hMSet($key, $data);

        Log::debug(sprintf('QixiUserDao::hMSetUser userId=%d data=%s',
            $userId, json_encode($data)));
    }
}