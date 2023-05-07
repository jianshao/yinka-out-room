<?php

namespace app\query\prop\dao;

use app\core\mysql\ModelDao;
use app\domain\prop\model\PropModel;

class PropModelDao extends ModelDao
{
    protected $serviceName = 'userSlave';
    protected $table = 'zb_user_props';
    protected $pk = 'id';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PropModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new PropModel();
        $model->propId = $data['id'];
        $model->kindId = $data['kind_id'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        $model->expiresTime = $data['expires_time'];
        $model->count = $data['count'];
        $model->isWore = $data['is_wore'];
        $model->woreTime = $data['wore_time'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'id' => $model->propId,
            'kind_id' => $model->kindId,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime,
            'expires_time' => $model->expiresTime,
            'count' => $model->count,
            'is_wore' => $model->isWore,
            'wore_time' => $model->woreTime,
        ];
    }

    /**
     * 加载userId用户所有的道具
     * 
     * @param userId: 哪个用户
     * @return: list<PropModel>
     */
    public function loadAllPropByUserId($userId) {
        $ret = [];
        $datas = $this->getModel($userId)->where(['uid' => $userId])->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }

    public function loadPropByKindId($userId, $kindId) {
        $data = $this->getModel($userId)->where(['uid' => $userId, 'kind_id' => $kindId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }
}


