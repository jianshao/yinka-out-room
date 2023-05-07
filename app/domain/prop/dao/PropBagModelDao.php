<?php


namespace app\domain\prop\dao;


use app\core\mysql\ModelDao;
use app\domain\prop\model\PropBagModel;

class PropBagModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_user_prop_bag';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PropBagModelDao();
            self::$instance->pk = 'uid';
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new PropBagModel();
        $model->nextId = $data['next_id'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'next_id' => $model->nextId,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime,
        ];
    }

    public function loadPropBag($userId) {
        $data = $this->getModel($userId)->where(['uid' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function createPropBag($userId, $propBagModel) {
        $data = $this->modelToData($propBagModel);
        $data['uid'] = $userId;
        $this->getModel($userId)->save($data);
    }

    public function updatePropBag($userId, $propBagModel) {
        $data = $this->modelToData($propBagModel);
        $this->getModel($userId)->where([
            'uid' => $userId
        ])->update($data);
    }
}
