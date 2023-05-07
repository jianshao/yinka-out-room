<?php

namespace app\domain\user\dao;
use app\core\mysql\ModelDao;

class CoinDao extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CoinDao();
        }
        return self::$instance;
    }

    public function loadCoin($userId) {
        $data = $this->getModel($userId)->field('gold_coin')->where(['id' => $userId])->find();
        if (!empty($data)) {
            return $data['gold_coin'];
        }
        return null;
    }

    public function incCoin($userId, $count) {
        assert($count >= 0);
        return $this->getModel($userId)->where(['id' => $userId])->inc('gold_coin', $count)->update();
    }

    /**
     * @param $userId
     * @param $count
     */
    public function decCoin($userId, $count) {
        assert($count >= 0);
        $whereStr = sprintf('id=%d and gold_coin >= %d', $userId, $count);
        return $this->getModel($userId)->whereRaw($whereStr)->dec('gold_coin', $count)->update();
    }
}