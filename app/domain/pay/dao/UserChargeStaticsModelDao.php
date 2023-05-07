<?php


namespace app\domain\pay\dao;


use app\core\mysql\ModelDao;
use app\domain\pay\model\UserChargeStaticsModel;

class UserChargeStaticsModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_user_charge_statics';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserChargeStaticsModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new UserChargeStaticsModel();
        $model->chargeAmount = $data['charge_amount'];
        $model->chargeTimes = $data['charge_times'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'charge_amount' => $model->chargeAmount,
            'charge_times' => $model->chargeTimes,
        ];
    }

    public function loadUserChargeStatics($userId) {
        $data = $this->getModel($userId)->where(['uid' => $userId])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function addAmountTimes($userId, $amount, $times) {
        $model = $this->loadUserChargeStatics($userId);
        if (empty($model)){
            $data = [
                'uid' => $userId,
                'charge_amount' => $amount,
                'charge_times' => $times
            ];
            $this->getModel($userId)->save($data);
        }else{
            $data = [
                'charge_amount' => $model->chargeAmount+$amount,
                'charge_times' => $model->chargeTimes+$times
            ];
            $this->getModel($userId)->where(['uid' => $userId])->update($data);
        }

    }
}