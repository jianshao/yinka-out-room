<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\AttentionModel;

class AttentionModelDao extends ModelDao
{
    protected $table = 'zb_user_attention';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AttentionModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new AttentionModel();
        $model->userId = $data['user_id'];
        $model->attentionId = $data['attention_id'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    /**
     * @param $userId
     * @param $userIdEd
     * @return AttentionModel|null
     */
    public function loadAttention($userId, $userIdEd) {
        $data = $this->getModel($userId)->where(['user_id' => $userId, 'attention_id' => $userIdEd])->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    public function addAttention($userId, $attentionId, $time) {
        $data['user_id'] = $userId;
        $data['attention_id'] = $attentionId;
        $data['create_time'] = $time;
        $this->getModel($userId)->save($data);
    }

    public function delAttention($userId, $attentionId) {
        $this->getModel($userId)->where(['user_id' => $userId, 'attention_id'=>$attentionId])->delete();
    }
}