<?php


namespace app\query\redpacket\dao;


use app\core\mysql\ModelDao;
use app\domain\redpacket\RedPacketModel;

class RedPacketModelDao extends ModelDao
{
    protected $serviceName = 'commonSlave';
    protected $table = 'zb_redpackets';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RedPacketModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new RedPacketModel();
        $model->id = $data['id'];
        $model->count = $data['red_num'];
        $model->totalBean = $data['red_countcoin'];
        $model->sendUserId = $data['send_uid'];
        $model->sendTime = $data['send_time'];
        $model->countdown = $data['count_down'];
        $model->roomId = $data['room_id'];
        $model->status = $data['status'];
        $model->createTime = $data['created_time'];
        $model->type = $data['type'];
        $model->orderId = $data['redpackets_num'];
        $model->dealId = $data['dealid'];
        return $model;
    }

    public function findByRoomId($roomId) {
        $where = [
            'room_id' => $roomId,
            'status' => 1
        ];
        $datas = $this->getModel()->where($where)->order('id asc')->select()->toArray();

        $ret = [];
        if (!empty($datas)) {
            foreach ($datas as $data){
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }

    public function findById($id) {
        $data = $this->getModel()->where(['id' => $id])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function modelToData($model) {
        return [
            'red_num' => $model->count,
            'red_countcoin' => $model->totalBean,
            'send_uid' => $model->sendUserId,
            'send_time' => $model->sendTime,
            'count_down' => $model->countdown,
            'room_id' => $model->roomId,
            'status' => $model->status,
            'created_time' => $model->createTime,
            'redpackets_num' => $model->orderId,
            'type' => $model->type,
            'dealid' => $model->dealId
        ];
    }
}