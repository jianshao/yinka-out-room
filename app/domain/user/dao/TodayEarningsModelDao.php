<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\TodayEarningsModel;

class TodayEarningsModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_today_earnings';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TodayEarningsModelDao();
        }
        return self::$instance;
    }

    public function loadTodayEarnings($userId) {
        $data = $this->getModel($userId)->where(['uid' => $userId])->find();
        if (empty($data)) {
            return null;
        }
        $ret = new TodayEarningsModel();
        $ret->diamond = $data['diamond'];
        $ret->updateTime = $data['update_time'];
        return $ret;
    }

    public function createTodayEarnings($userId, $model) {
        $data = [
            'uid' => $userId,
            'diamond' => $model->diamond,
            'update_time' => $model->updateTime
        ];
        $this->getModel($userId)->insert($data);
    }

    public function saveTodayEarnings($userId, $model) {
        $data = [
            'diamond' => $model->diamond,
            'update_time' => $model->updateTime
        ];
        $this->getModel($userId)->where(['uid' => $userId])->update($data);
    }
}