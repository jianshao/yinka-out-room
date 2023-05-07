<?php


namespace app\query\room\dao;


use app\core\mysql\ModelDao;

class RoomNameModelDao extends ModelDao
{
    protected $table = 'zb_room_name';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';
    protected $shardingId = 0;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomNameModelDao();
        }
        return self::$instance;
    }


    public function findRoomNames($roomId)
    {
        return $this->getModel($this->shardingId)->field('name')->where(['rm_id' => $roomId, 'status' => 1])->select()->toArray();
    }

    public function getAll()
    {
        return $this->getModel($this->shardingId)->field('name,type')->select()->toArray();
    }
}