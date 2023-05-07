<?php
namespace app\test\sharding;

use app\core\mysql\Sharding;

class userModelDao
{
    protected $serviceName = 'userMaster';
    protected $tableName = 'zb_member';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new userModelDao();
        }
        return self::$instance;
    }

    public function test($userId) {
        return Sharding::getInstance()->getModel($this->serviceName, $this->tableName, $userId)->where(['id' => $userId])->find();
    }

    public function tests($userIds) {
        $models = Sharding::getInstance()->getModels($this->serviceName, $this->tableName, $userIds);
        foreach($models as $model) {
            $datas = $model->model->where([['id', 'in', $model->list]])->column('username');
            print_r($datas).PHP_EOL;
        }
    }
}