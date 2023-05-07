<?php


namespace app\domain\appinfo\dao;

use app\core\mysql\ModelDao;

class ChannelDataModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_channel_data';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChannelDataModelDao();
        }
        return self::$instance;
    }

    public function addData($data) {
        $this->getModel()->insert($data);
    }
}