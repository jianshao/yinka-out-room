<?php


namespace app\domain\dao;

use app\core\mysql\ModelDao;

class UserReportModelDao extends ModelDao {
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_complaints';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserReportModelDao();
        }
        return self::$instance;
    }
}