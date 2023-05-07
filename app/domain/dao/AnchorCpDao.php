<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;

class AnchorCpDao extends ModelDao
{
    protected $table = 'zb_anchor_cp';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AnchorCpDao();
        }
        return self::$instance;
    }

    public function getAnchorStatus($userId) {
        $res = $this->getModel()->where(['user_id' => $userId, 'status' => 1])->find();
        if (!empty($res)) {
            return true;
        }
        return false;
    }
}