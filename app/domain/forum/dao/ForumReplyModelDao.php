<?php


namespace app\domain\forum\dao;
use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumReplyModel;

class ForumReplyModelDao extends ModelDao {

    protected $table = 'zb_forum_reply';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumReplyModelDao();
        }
        return self::$instance;
    }

    public function updateReadReplyByUserIds($userIds) {
        $this->getModel()->where([['reply_atuid', 'in', $userIds]])->update(['is_read'=>1]);
    }

    public function loadReplyModel($replyId) {
        $where[] = ['id', '=', $replyId];
        $data = $this->getModel()->lock(true)->where($where)->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function saveReplay($model) {
        $data = $this->modelToData($model);
        $this->getModel()->save($data);
    }

    public function updateReplay($model) {
        $data = $this->modelToData($model);
        $this->getModel()->where(['id' => $model->replyId])->update($data);
    }

    private function dataToModel($data) {
        $model = new ForumReplyModel();
        $model->replyId = $data['id'];
        $model->replyUid = $data['reply_uid'];
        $model->forumId = $data['forum_id'];
        $model->isRead = $data['is_read'];
        $model->content = $data['reply_content'];
        $model->parentId = $data['reply_parent_id'];
        $model->delUid = $data['reply_deluid'];
        $model->delTime = $data['reply_deltime'];
        $model->status = $data['reply_status'];
        $model->type = $data['reply_type'];
        $model->atUid = $data['reply_atuid'];
        $model->createTime = $data['createtime'];
        $model->updateTime = $data['updatetime'];
        return $model;
    }

    public function modelToData($model) {
        $data = [
            'reply_uid' => $model->replyUid,
            'forum_id' => $model->forumId,
            'is_read' => $model->isRead,
            'reply_content' => $model->content,
            'reply_parent_id' => $model->parentId,
            'reply_deluid' => $model->delUid,
            'reply_deltime' => $model->delTime,
            'reply_status' => $model->status,
            'reply_type' => $model->type,
            'reply_atuid' => $model->atUid,
            'createtime' => $model->createTime,
            'updatetime' => $model->updateTime
        ];
        return $data;
    }
}