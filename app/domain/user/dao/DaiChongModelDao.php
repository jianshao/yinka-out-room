<?php

namespace app\domain\user\dao;
use app\core\mysql\ModelDao;

class DaiChongModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_daichong';
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DaiChongModelDao();
        }
        return self::$instance;
    }

    public function getUserSubstitutePay($userId)
    {
        $where[] = ['uid', '=', $userId];
        $where[] = ['status', '=', 1];
        return $this->getModel()->where($where)->find();
    }
}