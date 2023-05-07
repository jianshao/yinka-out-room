<?php

namespace app\domain\feedback\dao;

use app\core\mysql\ModelDao;
class FeedbackModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_feedback';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new FeedbackModelDao();
        }
        return self::$instance;
    }

    public function modelToData($model) {
        return [
            'user_id' => $model->userId,
            'content' => $model->content,
            'create_time' => $model->createTime
        ];
    }

    public function addFeedback($feedbackModel) {
        $data = $this->modelToData($feedbackModel);
        $this->getModel()->insert($data);
    }
}