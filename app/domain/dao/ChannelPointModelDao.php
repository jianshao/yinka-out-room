<?php

namespace app\domain\dao;
use app\core\mysql\ModelDao;
use think\Model;

class ChannelPointModelDao extends ModelDao {
    protected $serviceName = 'biMaster';
    protected $table = 'bi_channel_points';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ChannelPointModelDao();
        }
        return self::$instance;
    }

    public function saveData($data) {
        $this->getModel()->save($data);
    }

}