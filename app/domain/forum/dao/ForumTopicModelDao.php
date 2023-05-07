<?php
/**
 * User: li
 * Date: 2019
 * 动态标签及话题数据表
 */
namespace app\domain\forum\dao;
use app\core\mysql\ModelDao;
use app\domain\forum\model\ForumTopicModel;

class ForumTopicModelDao extends ModelDao {

    protected $table = 'zb_forum_topic';
    protected $serviceName = 'commonMaster';
    protected static $instance;


    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumTopicModelDao();
        }
        return self::$instance;
    }

    public function loadTopicModel($id) {
        $data = $this->getModel($id)->where(['id' => $id])->find();
        if ($data) {
            return $this->dataToModel($data);
        }
        return null;
    }

    private function dataToModel($data) {
        $model = new ForumTopicModel();
        $model->id = $data['id'] ?? 0;
        $model->pid = $data['pid'] ?? 0;
        $model->topicName = $data['topic_name'] ?? "";
        $model->topicStatus = $data['topic_status'] ?? 0;
        $model->topicOrder = $data['topic_order'] ?? 0;
        $model->topicHot = $data['topic_hot'] ?? 0;
        $model->topicRecommend = $data['topic_recommend'] ?? 0;
        $model->createTime = $data['create_time'] ?? 0;
        $model->updateTime = $data['update_time'] ?? 0;
        $model->createUser = $data['create_user'] ?? "";
        $model->updateUser = $data['update_user'] ?? "";
        return $model;
    }
}