<?php


namespace app\domain\forum\dao;


use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumBlackModel;

class ForumBlackModelDao extends ModelDao
{
    protected $table = 'zb_forum_black';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumBlackModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new ForumBlackModel();
        $model->userId = $data['black_uid'];
        $model->toUserId = $data['toblack_uid'];
        $model->createTime = $data['createtime'];
        $model->updateTime = $data['updatetime'];
        return $model;
    }

    public function getBlackModel($userId, $toUserId){
        $data = $this->getModel()->where([
            'black_uid' => $userId,
            'toblack_uid' => $toUserId
        ])->find();

        if(!empty($data)){
            return $this->dataToModel($data);
        }
        return null;
    }

    public function delBlack($userId, $toUserId){
        $this->getModel()->where(['black_uid'=>$userId,'toblack_uid'=>$toUserId])->delete();
    }

    public function addBlack($userId, $toUserId){
        $this->getModel()->insert(['black_uid'=>$userId,'toblack_uid'=>$toUserId, 'createtime'=>time(), 'updatetime'=>time()]);
    }
}