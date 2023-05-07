<?php


namespace app\domain\lottery;


use app\core\mysql\ModelDao;


//金币抽奖
class CoinLotteryRewardModelDao extends ModelDao
{
    protected $table = 'zb_goldcoin_box';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CoinLotteryRewardModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new CoinLotteryRewardModel();
        $model->userId = $data['uid'];
        $model->rewardId = $data['reward_id'];
        $model->rewardType = $data['reward_type'];
        $model->num = $data['reward_desc'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'uid' => $model->userId,
            'reward_id' => (string)$model->rewardId ,
            'reward_type' => $model->rewardType ,
            'reward_desc' =>  (string)$model->num,
            'create_time' => $model->createTime
        ];
    }

    //金币抽奖记录总数量
    public function getCoinLotteryCount($userId){
        $filed = ['uid'=>$userId];
        return $this->getModel()->where($filed)->count();
    }

    //金币抽奖记录
    public function loadCoinLotteryModels($userId, $page, $pagenum=null){
        $ret = [];
        $filed = ['uid'=>$userId];
        $datas = $this->getModel()->where($filed)->limit($page, $pagenum)->order('create_time desc')->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function saveReward($model) {
        $data = $this->modelToData($model);
        $data['uid'] = $model->userId;
        $this->getModel()->insert($data);
    }
}