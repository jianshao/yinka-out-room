<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;

class IdDao extends ModelDao
{
    protected $table = 'zb_ids';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new IdDao();
        }
        return self::$instance;
    }

    public function getNextId($type) {
        $where = ['type' => $type];
        $nextId = $this->getModel()->lock(true)->where($where)->value('next_id');
        $this->getModel()->where($where)->save(['next_id' => $nextId + 1]);
        return $nextId;
    }
}