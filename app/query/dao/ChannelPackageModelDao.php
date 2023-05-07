<?php

namespace app\query\dao;

use app\core\mysql\ModelDao;

class ChannelPackageModelDao extends ModelDao {

    protected $serviceName = 'commonSlave';
    protected $table = 'zb_channel_version';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ChannelPackageModelDao();
        }
        return self::$instance;
    }

    public function getOne($where) {
        return $this->getModel()->where($where)->find();
    }
}