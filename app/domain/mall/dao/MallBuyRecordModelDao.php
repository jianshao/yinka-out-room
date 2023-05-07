<?php

namespace app\domain\mall\dao;

use app\core\mysql\ModelDao;
use app\domain\mall\MallIds;


//商城兑换记录 包括金币抽奖 金币兑换
class MallBuyRecordModelDao extends ModelDao
{
    public static $COIN_EXCHANGE = 'coin_exchange';
    public static $GASHAPON_EXCHANGE = 'gashapon_exchange'; # 扭蛋兑换
    public static $GASHAPON_SEND = 'gashapon_send'; # 扭蛋赠送
    public static $TAOJIN_EXCHANGE = 'taojin_exchange';

    protected $table = 'zb_reward_record';
    protected $pk = 'id';
    protected $serviceName = 'userMaster';
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MallBuyRecordModelDao();
        }
        return self::$instance;
    }

    /**
     * @param $data
     * @return MallBuyRecordModel
     */
    public function dataToModel($data) {
        $model = new MallBuyRecordModel($data['uid']);
        $model->id = $data['id'];
        $model->rewardId = $data['rewardId'];
        $model->consumeId = $data['consumeId'];
        $model->createTime = $data['createTime'];
        $model->count = $data['count'];
        $model->price = $data['price'];
        $model->from = $data['from'];
        $model->mallId = $data['mallId'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'uid' => $model->userId,
            'rewardId' => $model->rewardId,
            'consumeId' => $model->consumeId,
            'from' => $model->from,
            'createTime' => $model->createTime,
            'count' => $model->count,
            'price' => $model->price,
            'mallId' => $model->mallId
        ];
    }

    public function add($model) {
        $data = $this->modelToData($model);
        $this->getModel($model->userId)->insert($data);
    }

    //金币兑换记录总数量
    public function getCoinExchangeCount($userId){
        $where = ['uid' => $userId, 'mallId' => MallIds::$COIN];
        return $this->getModel($userId)->where($where)->count();
    }

    //金币兑换记录
    public function getCoinExchangeModels($userId, $offset, $count){
        $ret = [];
        $where = ['uid' => $userId, 'mallId' => MallIds::$COIN];
        $datas = $this->getModel($userId)->where($where)->limit($offset, $count)->order('createTime desc')->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function getTaoJinModels($userId, $offset, $count=null){
        $ret = [];
        $where = ['uid' => $userId, 'mallId' => MallIds::$ORE];
        $datas = $this->getModel($userId)->where($where)->limit($offset, $count)->order('createTime desc')->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }
}