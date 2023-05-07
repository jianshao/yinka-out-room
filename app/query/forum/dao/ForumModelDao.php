<?php


namespace app\query\forum\dao;


use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumModel;

class ForumModelDao extends ModelDao
{
    protected $table = 'zb_forum';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumModelDao();
        }
        return self::$instance;
    }

    public function getForumCountByWhere($where)
    {
        return $this->getModel()->where($where)->count();
    }

    public function loadForumModel($forumId)
    {
        $data = $this->getModel()->where(array('id' => $forumId))->find();
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

    public function findForumModelsByWhere($where, $start, $pagenum)
    {
        $ret = [];
        $datas = $this->getModel()->where($where)->limit($start, $pagenum)->order('createtime desc')->select()->toArray();
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $ret[] = $this->dataToModel($data);
            }
        }
        return $ret;
    }

    /**
     * @desc 查询帖子置顶排序
     * @param $where
     * @param $start
     * @param $pagenum
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getForumModelsWithTop($where, $start, $pagenum)
    {
        $ret = [];
        $datas = $this->getModel()->where($where)->limit($start, $pagenum)->order('is_top desc')->order('createtime desc')->select()->toArray();
        foreach ($datas as $data) {
            $ret[] = $this->dataToModel($data);
        }
        return $ret;
    }

    public function findForumModelMapByWhere($where)
    {
        $ret = [];
        $datas = $this->getModel()->where($where)->select()->toArray();
        if (!empty($datas)){
            foreach ($datas as $data) {
                $model = $this->dataToModel($data);
                $ret[$model->forumId] = $model;
            }
        }
        return $ret;
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
}