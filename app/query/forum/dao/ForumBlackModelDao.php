<?php


namespace app\query\forum\dao;


use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumBlackModel;

class ForumBlackModelDao extends ModelDao
{
    protected $table = 'zb_forum_black';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

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

    public function getBlackCount($userId)
    {
        return $this->getModel()->where(['black_uid'=>$userId])->count();
    }

    /**
     * 获取被拉黑人列表
     */
    public function getToblackIdByBlackId($userId){
        $res = $this->getModel()->where(['black_uid'=>$userId])->field('toblack_uid')->select();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }

    /**
     * 获取被拉黑人列表
     */
    public function getBlackModelsByBlackId($userId, $offset, $count){
        $datas = $this->getModel()->where(['black_uid'=>$userId])
            ->order('createtime desc')
            ->limit($offset, $count)
            ->select()
            ->toArray();

        $ret = [];
        if(!empty($datas)){
            foreach ($datas as $data){
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }
}