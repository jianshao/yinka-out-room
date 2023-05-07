<?php


namespace app\domain\feedback\dao;


use app\core\mysql\ModelDao;
use app\utils\TimeUtil;

class LoginFeedbackModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_login_feedback';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new LoginFeedbackModelDao();
        }
        return self::$instance;
    }

    public function addFeedback($model) {
        $data = [
            'account' => $model->account,
            'status' => $model->status,
            'phone' => $model->phone,
            'problem' => $model->problem,
            'mode' => $model->mode,
            'addtime' => TimeUtil::timeToStr($model->createTime),
        ];
        $this->getModel()->insert($data);
    }
}