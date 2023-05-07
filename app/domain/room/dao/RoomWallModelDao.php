<?php


namespace app\domain\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomWallModel;


class RoomWallModelDao extends ModelDao
{
    protected $table = 'zb_room_wall';
    protected $pk = 'id';
    protected $serviceName = 'roomMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RoomWallModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $ret = new RoomWallModel();
        $ret->roomId = $data['room_id'];
        $ret->roomType = $data['room_type'];
        $ret->photoId = $data['photo_id'];
        return $ret;
    }

    public function loadRoomWallByRoomType($roomId, $roomType) {
        $data = $this->getModel($roomId)->where(['room_id' => $roomId, 'room_type' => $roomType])->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }

    public function saveRoomWall($roomWall) {
        $data = [
            'room_id' => $roomWall->roomId,
            'room_type' => $roomWall->roomType,
            'photo_id' => $roomWall->photoId
        ];
        $this->getModel($roomWall->roomId)->insert($data);
    }

    public function updateRoomWall($roomWall) {
        $data = [
            'photo_id' => $roomWall->photoId
        ];
        $this->getModel($roomWall->roomId)->where(array("room_id" => $roomWall->roomId, "room_type" => $roomWall->roomType))->update($data);
    }
}