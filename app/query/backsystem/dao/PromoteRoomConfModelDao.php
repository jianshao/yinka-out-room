<?php


namespace app\query\backsystem\dao;


use app\core\mysql\ModelDao;
use think\Model;

class PromoteRoomConfModelDao extends ModelDao
{
    protected $serviceName = 'biSlave';
    protected $table = 'zb_promote_room_conf';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PromoteRoomConfModelDao();
        }
        return self::$instance;
    }

    public function getOne($where) {
        return $this->getModel()->where($where)->find();
    }

    public function getAll($where) {
        $data = $this->getModel()->where($where)->select();
        if (!empty($data)) {
            return $data->toArray();
        }
        return [];
    }

    public function getRoomIdByInviteCode($where) {
        return $this->getModel()->where($where)->value('room_id');
    }




}