<?php


namespace app\query\pay\dao;


use app\core\mysql\ModelDao;
use app\domain\pay\model\Order;
use app\utils\TimeUtil;

class OrderModelDao extends ModelDao
{
    protected $serviceName = 'commonSlave';
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

    public function getList($where, $offset, $limit)
    {
        $res = $this->getModel()->where($where)->order('addtime', 'desc')->limit($offset, $limit)->select()->toArray();
        if (empty($res)) {
            return [];
        }
        return $res;
    }

    public function getListPaginate($where, $page, $pagenum)
    {
        $ret = [];
        $offset = $page * $pagenum - $pagenum;
        $object = $this->getModel()->where($where)->order('finish_time', 'desc')->limit($offset, $pagenum)->select();
        if ($object === null) {
            return [0, []];
        }
        $datas = $object->toArray();
        $total = $this->getModel()->where($where)->count();
        if ($total > 0) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        if (empty($total)) {
            $total = 0;
        }
        return [$total, $ret];
    }

    public function getListModel($where, $offset, $limit)
    {
        $ids = $this->getModel()->where($where)->order('addtime', 'desc')->limit($offset, $limit)->column('id');
        if (empty($ids)) {
            return [];
        }
        $secwhere = [['id', 'in', $ids]];
        $res = $this->getModel()->where($secwhere)->field(['rmb,coin,content,status,addtime,orderno,platform,type,is_active,channel'])->select();
        return $res;
    }

    public function walletDetail($page, $pageNum, $userId, $queryStartTime, $queryEndTime, $type = [])
    {
        $where = [
            ['uid', '=', $userId],
            ['finish_time', '>=', $queryStartTime],
            ['finish_time', '<', $queryEndTime]
        ];
        if ($type) {
            $where [] = ['type', 'in', $type];
        }
        return $this->getListPaginate($where, $page, $pageNum);
    }

    public function getChargeCount($where) {
        return $this->getModel()->where($where)->count();
    }

}