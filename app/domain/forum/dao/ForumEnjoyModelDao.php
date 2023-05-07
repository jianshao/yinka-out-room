<?php


namespace app\domain\forum\dao;


use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumEnjoyModel;

class ForumEnjoyModelDao extends ModelDao {

    protected $table = 'zb_forum_enjoy';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumEnjoyModelDao();
        }
        return self::$instance;
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

    private function modelToData($model) {
        return [
            'enjoy_uid' => $model->enjoyUid,
            'forum_id' => $model->forumId,
            'is_read' => $model->isRead,
            'is_del' => $model->isDel,
            'createtime' => $model->createTime,
            'updatetime' => $model->updateTime,
        ];
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

    public function updateReadEnjoyByForumIds($forumIds) {
        $this->getModel()->where([['forum_id', 'in', $forumIds]])->update(['is_read'=>1]);
    }

    public function loadEnjoyModelByForumId($userId, $forumId) {
        $data = $this->getModel()->lock(true)->where(['forum_id' => $forumId, 'enjoy_uid' => $userId,'is_del'=>0])->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function loadEnjoyModel($id) {
        $data = $this->getModel()->lock(true)->where(['id' => $id])->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function saveEnjoy($model) {
        $data = $this->modelToData($model);
        $this->getModel()->save($data);
    }

    public function updateEnjoy($model) {
        $data = $this->modelToData($model);
        $this->getModel()->where(['id' => $model->id])->update($data);
    }

}