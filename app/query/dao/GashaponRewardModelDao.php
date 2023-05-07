<?php


namespace app\query\dao;


use app\core\mysql\ModelDao;
use app\domain\game\gashapon\GashaponRewardModel;


//扭蛋机抽奖
class GashaponRewardModelDao extends ModelDao
{
    protected $table = 'zb_gashapon_reward';
    protected $pk = 'id';
    protected $serviceName = 'userSlave';

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

    //金币抽奖记录总数量
    public function getGashaponCount($userId){
        $filed = ['uid'=>$userId];
        return $this->getModel($userId)->where($filed)->count();
    }

    public function loadSelfRewards($userId, $page, $pagenum) {
        $ret = [];
        $filed = ['uid'=>$userId];
        $datas = $this->getModel($userId)->where($filed)->limit($page, $pagenum)->order('create_time desc')->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }
}