<?php


namespace app\domain\game\taojin\dao;


use app\core\mysql\ModelDao;
use app\domain\game\taojin\model\TaoJinRewardModel;


//游戏奖励记录
class TaoJinRewardModelDao extends ModelDao
{
    protected $table = 'zb_game_log';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TaoJinRewardModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new TaoJinRewardModel();
        $model->id = $data['id'];
        $model->userId = $data['uid'];
        $model->rewardType = $data['type'];
        $model->num = $data['gift_num'];
        $model->gameId = $data['game_id'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    public function modelToData($model) {
        return [
            'uid' => $model->userId,
            'type' => $model->rewardType ,
            'gift_num' =>  $model->num,
            'game_id' => $model->gameId,
            'create_time' => $model->createTime
        ];
    }

    public function loadSelfRewards($userId, $gameId, $page, $pagenum) {
        $ret = [];
        $filed = ['uid'=>$userId, 'game_id'=>$gameId];
        $datas = $this->getModel($userId)->where($filed)->limit($page, $pagenum)->order('create_time desc')->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function loadGameRewards($gameId, $count, $page) {
        $ret = [];
        $filed = [['game_id', '=', $gameId],['gift_num','>=',$count]];
        $datas = $this->getModel($gameId)->where($filed)->limit($page)->order('create_time desc')->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function saveReward($model) {
        $data = $this->modelToData($model);
        $data['uid'] = $model->userId;
        $this->getModel($model->userId)->insert($data);
    }
}