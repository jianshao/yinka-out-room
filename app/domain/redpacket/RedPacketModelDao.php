<?php


namespace app\domain\redpacket;


use app\core\mysql\ModelDao;

class RedPacketModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_redpackets';
    protected $pk = 'id';
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

    public function findById($id)
    {
        $data = $this->getModel()->where(['id' => $id])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function findByRoomId($roomId)
    {
        $where = [
            'room_id' => $roomId,
            'status' => 1
        ];
        $datas = $this->getModel()->where($where)->order('id asc')->select()->toArray();

        $ret = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }

    public function findByOrderId($orderId)
    {
        $data = $this->getModel()->where(['redpackets_num' => $orderId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function modelToData($model)
    {
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

    public function createModel($model) {
        $datas = $this->modelToData($model);
        $model->id = intval($this->getModel()->insertGetId($datas));
        return $model;
    }

    public function updateModel($model, $expectStatus) {
        $res = $this->getModel()->where(['id' => $model->id, 'status' => $expectStatus])->update([
            'dealid' => $model->dealId,
            'status' => $model->status,
            'redpackets_num' => $model->orderId,
            'send_time' => $model->sendTime
        ]);
        if (empty($res)) {
            return false;
        }
        return true;
    }


    /**
     * @param $roomId
     * @return RedPacketModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadRedPackModelWithRoomId($roomId)
    {
        if (empty($roomId)) {
            return null;
        }
        $object = $this->getModel()->where(['room_id' => $roomId, 'status' => 1])->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    public function updateDatas($redPacketId, $data)
    {
        $this->getModel()->where(['id' => $redPacketId])->update($data);
    }
}