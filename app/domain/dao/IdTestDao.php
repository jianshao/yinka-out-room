<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;

class IdTestDao extends ModelDao
{
    protected $table = 'zb_test_ids';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new IdTestDao();
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