<?php


namespace app\query\forum\dao;
use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumReplyModel;

class ForumReplyModelDao extends ModelDao {

    protected $table = 'zb_forum_reply';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumReplyModelDao();
        }
        return self::$instance;
    }

    //统计评论
    public function getAllNum($str) {
        $where[] = ['forum_id', 'in', $str];
        $where[] = ['reply_status', '=', 1];
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


    /**
    //获取帖子未读数量
     * @param $userId
     * @return int
     */
    public function getUnreadCount($userId) {
        $where[] = ['reply_atuid','=', $userId];
        $where[] = ['is_read','=',0];
        return $this->getModel()->where($where)->count('id');
    }


    /**
    //获取回帖数量
     * @param $userId
     * @return int
     */
    public function getAtUidCount($userId) {
        $where[] = ['reply_atuid','=', $userId];
        return $this->getModel()->where($where)->count('id');
    }

    //获取帖子评论数量
    public function getReplyCount($forumId)
    {
        $where[] = ['forum_id', '=', $forumId];
        $where[] = ['reply_status', '=', 1];
        return $this->getModel()->where($where)->count('id');
    }

    public function findReplyModelsByForumId($forumId, $start, $pagenum) {
        $ret = [];
        $where[] = ['forum_id', '=', $forumId];
        $where[] = ['reply_status', '=', 1];
        $datas = $this->getModel()->where($where)->limit($start, $pagenum)->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function findReplyModelsByAtUid($atUid, $start, $pagenum) {
        $ret = [];
        $where[] = ['reply_atuid','=',$atUid];
        $datas = $this->getModel()->where($where)->order('createtime desc')->limit($start, $pagenum)->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
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