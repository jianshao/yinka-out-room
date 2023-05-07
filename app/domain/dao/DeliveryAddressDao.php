<?php


namespace app\domain\dao;

use app\core\mysql\ModelDao;
use app\domain\models\DeliveryAddressModel;


class DeliveryAddressDao extends ModelDao
{
    protected $table = 'zb_delivery_address';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DeliveryAddressDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new DeliveryAddressModel();
        $model->userId = $data['user_id'];
        $model->name = $data['name'];
        $model->mobile = $data['mobile'];
        $model->region = $data['region'];
        $model->address = $data['address'];
        $model->reward = $data['reward'];
        $model->count = $data['count'];
        $model->createTime = $data['createTime'];
        $model->activityType = $data['activity_type'];
        return $model;
    }

    public function modelToData($model)
    {
        return [
            'user_id' => $model->userId,
            'name' => $model->name,
            'mobile' => $model->mobile,
            'region' => $model->region,
            'address' => $model->address,
            'reward' => $model->reward,
            'count' => $model->count,
            'create_time' => $model->createTime,
            'activity_type' => $model->activityType
        ];
    }

    public function loadModel($userId, $activityType) {
        $where[] = [
            ['user_id', '=', $userId],
            ['activity_type', '=', $activityType]
        ];
        $data = $this->getModel($userId)->lock(true)->where($where)->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function addData($model) {
        $data = $this->modelToData($model);
        return $this->getModel($model->userId)->save($data);
    }
}