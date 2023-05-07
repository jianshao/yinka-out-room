<?php

namespace app\domain\game\taojin\dao;
use app\core\mysql\ModelDao;

class EnergyDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new EnergyDao();
        }
        return self::$instance;
    }

    public function loadEnergy($userId) {
        $data = $this->getModel($userId)->field('energy')->where(['id' => $userId])->find();
        if (!empty($data)) {
            return $data['energy'];
        }
        return null;
    }

    public function incEnergy($userId, $count) {
        assert($count >= 0);
        return $this->getModel($userId)->where(['id' => $userId])->inc('energy', $count)->update();
    }

    /**
     * @param $userId
     * @param $count
     */
    public function decEnergy($userId, $count) {
        assert($count >= 0);
        $whereStr = sprintf('id=%d and energy >= %d', $userId, $count);
        return $this->getModel($userId)->whereRaw($whereStr)->dec('energy', $count)->update();
    }
}