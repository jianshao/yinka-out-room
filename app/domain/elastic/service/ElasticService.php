<?php


namespace app\domain\elastic\service;


use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\query\room\elastic\RoomModelElasticDao;
use app\query\user\elastic\UserModelElasticDao;

class ElasticService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ElasticService();
        }
        return self::$instance;
    }

    /**
     * @info 同步房间模型数据
     * @param $roomId
     * @return bool|null
     */
    public function syncRoomModel($roomId)
    {
        if (empty($roomId)) {
            return null;
        }
        $model = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($model === null) {
            return null;
        }
        return RoomModelElasticDao::getInstance()->storeData($roomId, $model);
    }

    /**
     * @info 同步用户模型数据
     * @param $userId
     * @return bool|null
     */
    public function syncUserModel($userId)
    {
        if (empty($userId)) {
            return null;
        }
        $model = UserModelDao::getInstance()->loadUserModel($userId);
        if ($model === null) {
            return null;
        }
        return UserModelElasticDao::getInstance()->storeData($userId, $model);
    }
}