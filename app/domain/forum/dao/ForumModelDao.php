<?php


namespace app\domain\forum\dao;


use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumModel;

class ForumModelDao extends ModelDao
{
    protected $table = 'zb_forum';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumModelDao();
        }
        return self::$instance;
    }

    public function loadSelfForumModel($userId, $forumId)
    {
        $data = $this->getModel()->lock(true)->where(['id' => $forumId, 'forum_uid' => $userId])->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function loadForumModel($forumId)
    {
        $data = $this->getModel()->where(array('id' => $forumId))->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function loadWhereForumModel($where)
    {
        $data = $this->getModel()->where($where)->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function loadForumModelWhere($where)
    {
        $data = $this->getModel()->where($where)->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }


    public function getUserForumImage($where, $page, $pageNum) {
        $start = ($page-1) * $pageNum;
        return $this->getModel()->where($where)->limit($start, $pageNum)->order('is_top desc')->order('createtime desc')->column('forum_image');
    }

    public function insertForum($model)
    {
        $data = [
            "forum_uid" => $model->forumUid,
            "forum_content" => $model->content,
            "forum_image" => $model->image,
            "forum_voice" => $model->voice,
            "createtime" => $model->createTime,
            "updatetime" => $model->updateTime,
            "forum_voice_time" => $model->voiceTime,
            "tid" => $model->tid,
            "forum_status" => 3,
            "location" => $model->location
        ];
        $this->getModel()->insert($data);
    }

    public function updateForum($model)
    {
        $data = $this->modelToData($model);
        $this->getModel()->where(['id' => $model->forumId])->update($data);
    }

    public function incShareNum($forumId, $shareNum)
    {
        assert($shareNum >= 0);
        $this->getModel()->where(['id' => $forumId])->inc('share_num', $shareNum)->update();
    }

    private function dataToModel($data)
    {
        $model = new ForumModel();
        $model->forumId = $data['id'];
        $model->forumUid = $data['forum_uid'];
        $model->content = $data['forum_content'];
        $model->image = $data['forum_image'];
        $model->voiceTime = $data['forum_voice_time'];
        $model->voice = $data['forum_voice'];
        $model->aliExamine = $data['ali_examine'];
        $model->aliExamineTime = $data['ali_examine_time'];
        $model->aliExamineImgJson = $data['ali_examine_imgjson'];
        $model->aliExamineVoiceJson = $data['ali_examine_voicejson'];
        $model->selfDelUid = $data['forum_selfdeluid'];
        $model->selfDelTime = $data['forum_selfdeltime'];
        $model->delUid = $data['forum_deluid'];
        $model->delTime = $data['forum_deltime'];
        $model->status = $data['forum_status'];
        $model->baseNum = $data['forum_basenum'];
        $model->examinedTime = $data['examined_time'];
        $model->tid = $data['tid'];
        $model->createTime = $data['createtime'];
        $model->updateTime = $data['updatetime'];
        $model->location = $data['location'];
        $model->shareNum = $data['share_num'];
        $model->isTop = $data['is_top'];
        return $model;
    }

    public function modelToData($model)
    {
        $data = [
            'forum_uid' => $model->forumUid,
            'forum_content' => $model->content,
            'forum_image' => $model->image,
            'forum_voice_time' => $model->voiceTime,
            'forum_voice' => $model->voice,
            'ali_examine' => $model->aliExamineTime,
            'ali_examine_imgjson' => $model->aliExamineImgJson,
            'ali_examine_voicejson' => $model->aliExamineVoiceJson,
            'forum_selfdeluid' => $model->selfDelUid,
            'forum_selfdeltime' => $model->selfDelTime,
            'forum_deluid' => $model->delUid,
            'forum_deltime' => $model->delTime,
            'forum_status' => $model->status,
            'forum_basenum' => $model->baseNum,
            'examined_time' => $model->examinedTime,
            'tid' => $model->tid,
            'createtime' => $model->createTime,
            'updatetime' => $model->updateTime,
            'location' => $model->location,
            'share_num' => $model->shareNum,
            'is_top' => $model->isTop,
        ];
        return $data;
    }

    // 根据用户id标记动态为封号删除状态
    public function delForUserId($userId)
    {
        if (empty($userId)) {
            return 0;
        }
        $where[] = ["forum_uid", "=", $userId];
        $where[] = ["forum_status", "=", 1];
        return $this->getModel()->where($where)->update(['forum_status' => 5]);
    }

    // 根据用户id恢复封号删除的动态状态为正常
    public function rollbackForUserId($userId)
    {
        if (empty($userId)) {
            return 0;
        }
        $where[] = ["forum_uid", "=", $userId];
        $where[] = ["forum_status", "=", 5];
        return $this->getModel()->where($where)->update(['forum_status' => 1]);
    }

}