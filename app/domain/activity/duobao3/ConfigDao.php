<?php


namespace app\domain\activity\duobao3;

use app\core\mysql\ModelDao;


class ConfigDao extends ModelDao
{
    protected $table = 'zb_treasure_pool';
    protected $pk = 'id';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ConfigDao();
        }
        return self::$instance;
    }

    public function getPools(){
        return $this->getModel(0)->where(['status' => 1])->select()->toArray();
    }
}