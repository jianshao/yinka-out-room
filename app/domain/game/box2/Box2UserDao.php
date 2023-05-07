<?php


namespace app\domain\game\box2;


use app\common\RedisCommon;
use think\facade\Log;

class Box2UserDao
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Box2UserDao();
        }
        return self::$instance;
    }

    public function buildKey($boxId, $userId) {
        return 'box2:' . $boxId;
    }

    public function removeBoxUser($userId, $boxId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($boxId, $userId);
        $redis->hDel($key, $userId);
    }

    public function loadBoxUser($userId, $boxId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($boxId, $userId);
        $jstr = $redis->hGet($key, $userId);

        $ret = null;
        if (!empty($jstr)) {
            try {
                $jsonObj = json_decode($jstr, true);
                $ret = new Box2User($userId, $boxId);
                $ret->fromJson($jsonObj);
            } catch (\Exception $e) {
                Log::error(sprintf('Box2UserDao::loadBoxUser BadData userId=%d boxId=%d data=%s',
                    $userId, $boxId, $jstr));
            }
        }

        Log::debug(sprintf('Box2UserDao::loadBoxUser userId=%d boxId=%d data=%s',
            $userId, $boxId, $jstr));

        if ($ret == null) {
            $ret = new Box2User($userId, $boxId);
        }
        return $ret;
    }

    public function saveBoxUser(Box2User $boxUser) {
        $jsonObj = $boxUser->toJson();
        $jstr = json_encode($jsonObj);
        $redis = RedisCommon::getInstance()->getRedis();
        $key = $this->buildKey($boxUser->boxId, $boxUser->userId);
        $redis->hSet($key, $boxUser->userId, $jstr);

        Log::debug(sprintf('Box2UserDao::saveBoxUser userId=%d boxId=%d data=%s',
            $boxUser->userId, $boxUser->boxId, $jstr));
    }
}