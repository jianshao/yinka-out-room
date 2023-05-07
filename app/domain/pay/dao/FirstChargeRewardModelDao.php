<?php

namespace app\domain\pay\dao;

use app\core\mysql\ModelDao;
use app\domain\pay\model\FirstChargeRewardModel;

/**
 * @desc 首充奖励操作模型
 * Class FirstChargeRewardModelDao
 * @package app\domain\pay\dao
 */

class FirstChargeRewardModelDao extends ModelDao
{
    protected $table = 'zb_first_charge_reward';
    protected $pk = 'id';
    protected $serviceName = 'userMaster';

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FirstChargeRewardModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data): FirstChargeRewardModel
    {
        $model = new FirstChargeRewardModel();
        $model->userId = $data['user_id'];
        $model->currentDay = $data['current_day'];
        $model->rewards = $data['rewards'];
        $model->createdTime = $data['created_time'];
        return $model;
    }

    public function modelToData(FirstChargeRewardModel $model): array
    {
        return [
            'user_id' => $model->userId,
            'current_day' => $model->currentDay,
            'rewards' => $model->rewards,
            'created_time' => $model->createdTime
        ];
    }

    /**
     * @desc 用户首充奖励
     * @param $userId
     * @return array
     */
    public function getRewardsByUserId($userId)
    {
        return $this->getModel($userId)->where(['user_id' => $userId])->order('id','asc')->select()->toArray();
    }

    /**
     * @desc 插入数据
     * @param FirstChargeRewardModel $model
     * @return int|string
     */
    public function storeModel(FirstChargeRewardModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($model->userId)->insertGetId($data);
    }
}