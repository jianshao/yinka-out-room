<?php


namespace app\domain\dao;


use app\core\mysql\ModelDao;

class ReyunModelDao extends ModelDao
{
    protected $table = 'zb_reyun';
    protected $serviceName = 'commonMaster';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ReyunModelDao();
        }
        return self::$instance;
    }

    public function saveData($data) {
        $this->getModel()->insert($data);
    }
}