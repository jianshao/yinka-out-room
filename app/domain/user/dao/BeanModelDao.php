<?php

namespace app\domain\user\dao;

use app\core\mysql\ModelDao;
use app\domain\user\model\BeanModel;


class BeanModelDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BeanModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        return new BeanModel($data['totalcoin'], $data['freecoin']);
    }

    /**
     * 根据用户ID加载豆
     * 
     * @param userId: 哪个用户
     * @return BeanModel 找到返回BeanModel，没有则返回null
     */
    public function loadBean($userId) {
        $data = $this->getModel($userId)->field('totalcoin,freecoin')->where(['id' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * @param $userId
     * @param $count
     */
    public function incTotal($userId, $count) {
        assert($count >= 0);
        return $this->getModel($userId)->where(['id' => $userId])->inc('totalcoin', $count)->update();
    }

    /**
     * @param $userId
     * @param $count
     * @return \app\core\model\BaseModel
     */
    public function incFree($userId, $count) {
        $whereStr = sprintf('id=%d and totalcoin >= freecoin + %d', $userId, $count);
        return $this->getModel($userId)->whereRaw($whereStr)->inc('freecoin', $count)->update();
    }

    /**
     * 保存用户bean
     * 
     * @param userId: 哪个用户
     * @param model: 数据
     */
    public function saveBean($userId, $model) {
        $datas = [
            'totalcoin' => $model->total,
            'freecoin' => $model->free
        ];
        return $this->getModel($userId)->where(['id' => $userId])->update($datas);
    }
}