<?php
namespace app\domain\duke\dao;

use app\core\mysql\ModelDao;

class DukeLogModelDao extends ModelDao {
    protected $serviceName = 'userMaster';
    protected $table = 'zb_duke_log';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DukeLogModelDao();
        }
        return self::$instance;
    }

    public function addDukeLog($userId, $dukeLevel, $createTime) {
        $this->getModel($userId)->insert([
            'uid' => $userId,
            'duke_id' => $dukeLevel,
            'create_time' => $createTime
        ]);
    }
}