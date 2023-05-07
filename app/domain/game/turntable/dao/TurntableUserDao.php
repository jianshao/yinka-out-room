<?php


namespace app\domain\game\turntable\dao;


use app\common\RedisCommon;
use think\facade\Log;

class TurntableUserDao
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TurntableUserDao();
        }
        return self::$instance;
    }

    public function buildKey($turntableId, $userId) {
        return 'turntable:' . $turntableId;
    }

    public function removeBoxUser($userId, $turntableId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($turntableId, $userId);
        $redis->hDel($key, $userId);
    }

    public function loadBoxUser($userId, $turntableId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($turntableId, $userId);
        $jstr = $redis->hGet($key, $userId);

        $ret = null;
        if (!empty($jstr)) {
            try {
                $jsonObj = json_decode($jstr, true);
                $ret = new TurntableUser($userId, $turntableId);
                $ret->fromJson($jsonObj);
            } catch (\Exception $e) {
                Log::error(sprintf('TurntableUserDao::loadBoxUser BadData userId=%d turntableId=%d data=%s',
                    $userId, $turntableId, $jstr));
            }
        }

        Log::debug(sprintf('TurntableUserDao::loadBoxUser userId=%d turntableId=%d data=%s',
            $userId, $turntableId, $jstr));

        if ($ret == null) {
            $ret = new TurntableUser($userId, $turntableId);
        }
        return $ret;
    }

    public function saveBoxUser(TurntableUser $boxUser) {
        $jsonObj = $boxUser->toJson();
        $jstr = json_encode($jsonObj);
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($boxUser->turntableId, $boxUser->userId);
        $redis->hSet($key, $boxUser->userId, $jstr);

        Log::debug(sprintf('TurntableUserDao::saveBoxUser userId=%d turntableId=%d data=%s',
            $boxUser->userId, $boxUser->turntableId, $jstr));
    }
}