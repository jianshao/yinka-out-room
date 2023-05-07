<?php

namespace app\domain\user\dao;
use app\core\mysql\ModelDao;
use app\domain\user\model\DiamondModel;

class DiamondModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected $serviceName = 'userMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DiamondModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        return new DiamondModel($data['diamond'],
            $data['free_diamond'],
            $data['exchange_diamond']);
    }

    /**
     * 根据用户ID加载钻石
     * 
     * @param userId: 哪个用户
     * @return DiamondModel 找到返回DiamondModel，没有则返回null
     */
    public function loadDiamond($userId) {
        $data = $this->getModel($userId)->field('diamond,free_diamond,exchange_diamond')->where(['id' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * 增加总数量
     *
     * @param $userId
     * @param $count
     * @return mixed
     */
    public function incTotal($userId, $count) {
        assert($count >= 0);
        $this->getModel($userId)->where(['id' => $userId])->inc('diamond', $count)->update();
    }

    /**
     * 增加兑换数量
     *
     * @param $userId
     * @param $count
     * @return mixed
     */
    public function incFree($userId, $count) {
        assert($count >= 0);
        $whereStr = sprintf('id=%d and diamond >= exchange_diamond + free_diamond + %d', $userId, $count);
        return $this->getModel($userId)->whereRaw($whereStr)->inc('free_diamond', $count)->update();
    }

    /**
     * 增加兑换数量
     *
     * @param $userId
     * @param $count
     * @return mixed
     */
    public function incExchange($userId, $count) {
        assert($count >= 0);
        $whereStr = sprintf('id=%d and diamond >= exchange_diamond + free_diamond + %d', $userId, $count);
        return !!$this->getModel($userId)->whereRaw($whereStr)->inc('exchange_diamond', $count)->update();
    }

    /**
     * 保存用户钻石
     * 
     * @param userId: 哪个用户
     * @param model: 数据
     */
    public function saveDiamond($userId, $model) {
        $datas = [
            'diamond' => $model->total,
            'free_diamond' => $model->free,
            'exchange_diamond' => $model->exchange
        ];
        return $this->getModel($userId)->where(['id' => $userId])->update($datas);
    }
}