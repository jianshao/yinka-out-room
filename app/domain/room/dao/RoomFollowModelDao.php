<?php


namespace app\domain\room\dao;


use app\core\mysql\ModelDao;
use app\domain\room\model\RoomFollowModel;

class RoomFollowModelDao extends ModelDao
{
    protected $table = 'zb_room_follow';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $ret = new RoomFollowModel();
        $ret->userId = $data['user_id'];
        $ret->roomId = $data['room_id'];
        $ret->createTime = $data['creat_time'];
        return $ret;
    }

    public function loadFollow($roomId, $userId) {
        $where = [
            'room_id' => $roomId,
            'user_id' => $userId
        ];
        $data = $this->getModel($userId)->where($where)->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function delFollow($roomId, $userId) {
        $where = [
            'room_id' => $roomId,
            'user_id' => $userId
        ];
        $this->getModel($userId)->where($where)->delete();
    }

    public function addFollow($roomId, $userId, $timestamp) {
        $data = [
            'room_id' => $roomId,
            'user_id' => $userId,
            'creat_time' => $timestamp
        ];
        $this->getModel($userId)->insert($data);
    }

}