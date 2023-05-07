<?php

namespace app\domain\user\dao;
use app\core\mysql\ModelDao;

class UserLastInfoDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_user_last_info';
    protected $pk = 'user_id';
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserLastInfoDao();
        }
        return self::$instance;
    }

    public function getUserInfo($userId) {
        return $this->getModel($userId)->where(['user_id' => $userId])->find();
    }

    public function saveData($userId, $data) {
        $this->getModel($userId)->where(['user_id' => $userId])->save($data);
    }

    public function addData($userId, $data) {
        $this->getModel($userId)->insert($data);
    }



}