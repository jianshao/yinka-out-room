<?php


namespace app\domain\pay\dao;


use app\core\mysql\ModelDao;
use app\domain\pay\model\Order;
use app\utils\TimeUtil;

class OrderModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_chargedetail';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new OrderModelDao();
        }
        return self::$instance;
    }

    public function modelToData($order)
    {
        return [
            'uid' => $order->userId,
            'rmb' => $order->rmb,
            'coin' => $order->bean,
            'content' => $order->content,
            'status' => $order->status,
            'orderno' => $order->orderId,
            'addtime' => TimeUtil::timeToStr($order->createTime),
            'finish_time' => $order->finishTime,
            'paid_time' => $order->paidTime,
            'dealid' => $order->dealId,
            'platform' => $order->payChannel,
            'title' => $order->title,
            'type' => $order->type,
            'is_active' => $order->isActive,
            'channel' => $order->channel,
            'outparam' => $order->outParam,
            'product_id' => $order->productId
        ];
    }

    public function createOrder($order)
    {
        $data = $this->modelToData($order);
        $this->getModel()->insert($data);
    }

    public function dataToModel($data)
    {
        $model = new Order();
        $model->orderId = $data['orderno'];
        $model->userId = $data['uid'];
        $model->rmb = $data['rmb'];
        $model->bean = $data['coin'];
        $model->content = $data['content'];
        $model->status = $data['status'];
        $model->createTime = TimeUtil::strToTime($data['addtime']);
        $model->finishTime = $data['finish_time'];
        $model->paidTime = $data['paid_time'];
        $model->dealId = $data['dealid'];
        $model->payChannel = $data['platform'];
        $model->title = $data['title'];
        $model->type = $data['type'];
        $model->isActive = $data['is_active'];
        $model->channel = $data['channel'];
        $model->outParam = $data['outparam'];
        $model->productId = $data['product_id'];
        return $model;
    }

    public function loadOrder($orderId)
    {
        $data = $this->getModel()->where(['orderno' => $orderId])->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }

    public function findOrderByDealId($dealId)
    {
        $data = $this->getModel()->where(['dealid' => $dealId])->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }

    public function updateOrder($orderId, $updateDatas, $expectStatus)
    {
        $res = $this->getModel()->where([
            'orderno' => $orderId,
            'status' => $expectStatus
        ])->update($updateDatas);
        if (empty($res)) {
            return false;
        }
        return true;
    }

    public function getList($where, $offset, $limit)
    {
        $res = $this->getModel()->where($where)->order('addtime', 'desc')->limit($offset, $limit)->select()->toArray();
        if (empty($res)) {
            return [];
        }
        return $res;
    }


    /**
     * @desc 根据条件获取信息
     * @param $where
     * @param $field
     */
    public function getInfo($where, $field = '*')
    {
        $data = $this->getModel()->field($field)->where($where)->order('addtime')->find();
        if (empty($data)) {
            return null;
        }
        return $this->dataToModel($data);
    }
}