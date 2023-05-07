<?php

namespace app\domain\prop\dao;
use app\core\mysql\ModelDao;
use app\domain\prop\model\PropModel;

class PropModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
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

    /**
     * 保存道具
     *
     * @param $userId
     * @param $propModel
     */
    public function insertProp($userId, $propModel) {
        $data = $this->modelToData($propModel);
        $data['uid'] = $userId;
        $this->getModel($userId)->insert($data);
    }


    /**
     * 保存道具
     *
     * @param $userId
     * @param $propModel
     */
    public function updateProp($userId, $propModel) {
        $data = $this->modelToData($propModel);
        unset($data['kind_id']);
        $this->getModel($userId)->where([
            'uid' => $userId,
            'kind_id' => $propModel->kindId
        ])->update($data);
    }

    /**
     * 删除道具
     *
     * @param $userId
     * @param $propId
     * @return bool
     * @throws \Exception
     */
    public function removeProp($userId, $propId) {
        return $this->getModel($userId)->where(['uid' => $userId, 'id' => $propId])->delete();
    }
}


