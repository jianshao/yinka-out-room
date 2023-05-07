<?php


namespace app\domain\game\gashapon;


use app\core\mysql\ModelDao;


//扭蛋机抽奖
class GashaponRewardModelDao extends ModelDao
{
    protected $table = 'zb_gashapon_reward';
    protected $pk = 'id';
    protected $serviceName = 'userMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GashaponRewardModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new GashaponRewardModel();
        $model->userId = $data['uid'];
        $model->rewardId = $data['reward_id'];
        $model->rewardCount = $data['reward_count'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'uid' => $model->userId,
            'reward_id' => (string)$model->rewardId ,
            'reward_count' => $model->rewardCount ,
            'create_time' => $model->createTime
        ];
    }

    public function saveReward($model) {
        $data = $this->modelToData($model);
        $data['uid'] = $model->userId;
        $this->getModel($model->userId)->insert($data);
    }
}