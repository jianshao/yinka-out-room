<?php


namespace app\domain\activity\king;


use app\common\RedisCommon;
use think\facade\Log;

class KingUserDao
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new KingUserDao();
        }
        return self::$instance;
    }

    public function buildKey() {
        return 'king_user';
    }

    public function loadUser($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey();
        $jstr = $redis->hget($key, $userId);

        $ret = null;
        if (!empty($jstr)) {
            try {
                $jsonObj = json_decode($jstr, true);
                $ret = new KingUser($userId);
                $ret->fromJson($jsonObj);
            } catch (\Exception $e) {
                Log::error(sprintf('KingUserDao loadUser BadData userId=%d data=%s trace=%s',
                    $userId, $jstr, $e->getTraceAsString()));
            }
        }

        if ($ret == null) {
            $ret = new KingUser($userId);
        }

        Log::debug(sprintf('KingUserDao::loadUser userId=%d data=%s ret=%s',
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

        Log::debug(sprintf('KingUserDao::saveUser userId=%d data=%s',
            $user->userId, $jstr));
    }
}