<?php

namespace app\domain\room;

use app\domain\room\dao\RoomModelDao;

class RoomRepository
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RoomRepository();
        }
        return self::$instance;
    }

    /**
     * 加载房间
     * 
     * @param roomId: 要加载的房间ID
     * 
     * @return: 如果房间存在返回Room, 没找到返回null
     */
    public function loadRoom($roomId) {
        $model = RoomModelDao::getInstance()->loadRoomWithLock($roomId);
        if ($model == null) {
            return null;
        }
        return $this->newRoom($model);
    }

    public function newRoom($roomModel) {
        $room = new Room($roomModel);
        return $room;
    }
}


