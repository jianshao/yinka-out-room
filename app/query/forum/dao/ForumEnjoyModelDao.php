<?php


namespace app\query\forum\dao;


use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumEnjoyModel;

class ForumEnjoyModelDao extends ModelDao {

    protected $table = 'zb_forum_enjoy';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumEnjoyModelDao();
        }
        return self::$instance;
    }

    public function getAllNum($str) {
        $where[] = ['forum_id', 'in', $str];
        $where[] = ['is_del', '=', 0];
        $res = $this->getModel()->field('count(id) as num,forum_id')
            ->where($where)
            ->group('forum_id')
            ->select()
            ->toArray();
        if (!$res) {
            return [];
        }
        return $res;
    }

    //点赞数
    public function getEnjoyCount($forumId){
        $where[] = ['forum_id', '=', $forumId];
        $where[] = ['is_del', '=', 0];
        return $this->getModel()->where($where)->count();
    }

    //用户总点赞数
    public function getUserEnjoyCount($userId){
        $where[] = ['enjoy_uid', '=', $userId];
        return $this->getModel()->where($where)->count();
    }

    //获取userId发过的所有帖子的点赞量
    public function getUserForumCount($forumIds) {
        $count = 0;
        foreach ($forumIds as $forumId){
            $where[] = ['forum_id','=', $forumId];
            $where[] = ['is_del','=',0];
            $count += $this->getModel()->where($where)->count();
        }
        return $count;
    }

    //获取该帖子的点赞未读量
    public function getUserForumUnreadCount($forumIds)
    {
        $count = 0;
        foreach ($forumIds as $forumId){
            $where[] = ['forum_id','=', $forumId];
            $where[] = ['is_read','=',0];
            $where[] = ['is_del','=',0];
            $count += $this->getModel()->where($where)->count();
        }
        return $count;
    }

    public function findEnjoyModelMapByWhere($where) {
        $ret = [];
        $datas = $this->getModel()->where($where)->select()->toArray();
        foreach ($datas as $data) {
            $model = $this->dataToModel($data);
            $ret[$model->forumId] = $model;
        }
        return $ret;
    }

    public function findEnjoyModelsByWhere($where, $start,$pageNum) {
        $ret = [];
        $datas = $this->getModel()->where($where)
            ->order('createtime desc')
            ->limit($start,$pageNum)
            ->select()->toArray();

        foreach ($datas as $data) {
            $model = $this->dataToModel($data);
            $ret[] = $model;
        }
        return $ret;
    }

    public function findLastEnjoyModelByWhere($where) {
        $data = $this->getModel()->where($where)->limit(1)->order('id desc')->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function loadEnjoyModelByForumId($userId, $forumId) {
        $data = $this->getModel()->where(['forum_id' => $forumId, 'enjoy_uid' => $userId,'is_del'=>0])->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    private function dataToModel($data) {
        $model = new ForumEnjoyModel();
        $model->id = $data['id'];
        $model->enjoyUid = $data['enjoy_uid'];
        $model->forumId = $data['forum_id'];
        $model->isRead = $data['is_read'];
        $model->isDel = $data['is_del'];
        $model->createTime = $data['createtime'];
        $model->updateTime = $data['updatetime'];
        return $model;
    }
}