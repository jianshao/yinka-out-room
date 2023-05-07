<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\UserOnlineModel;

class UserOnlineModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_user_online_census';
    protected $pk = 'id';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserOnlineModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        return new UserOnlineModel($data['user_id'], $data['date'], $data['online_second'], $data['id']);
    }

    public function loadUserOnline($userId, $date) {
        $data = $this->getModel($userId)->where(['user_id' => $userId, 'date' => $date])->find();
        if (empty($data)) {
            return null;
        }

        return $this->dataToModel($data);
    }

    public function addData($model) {
        $data['user_id'] = $model->userId;
        $data['date'] = $model->date;
        $data['online_second'] = $model->onlineSecond;
        $this->getModel($model->userId)->insert($data);
    }

    public function incOnlineSecond($model) {
        assert($model->onlineSecond > 0,'fatal error');
        $this->getModel($model->userId)->where(['id' => $model->id])->inc('online_second', $model->onlineSecond)->update();
    }
}