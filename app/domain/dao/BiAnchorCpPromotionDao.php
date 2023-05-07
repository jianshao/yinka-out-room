<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;

class BiAnchorCpPromotionDao extends ModelDao
{
    protected $table = 'bi_anchor_cp_promotion';
    protected $serviceName = 'biMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiAnchorCpPromotionDao();
        }
        return self::$instance;
    }

    public function findOne($where)
    {
        return $this->getModel()->where($where)->find();
    }

    public function insertData($data) {
        return $this->getModel()->insert($data);
    }
}