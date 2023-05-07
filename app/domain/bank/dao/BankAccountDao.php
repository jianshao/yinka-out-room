<?php

namespace app\domain\bank\dao;

use app\core\mysql\ModelDao;

class BankAccountDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_user_bank';
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BankAccountDao();
        }
        return self::$instance;
    }

    /**
     * 加载用户银行账户
     * 
     * @param userId: 哪个用户
     * 
     * @return: map<account, count>
     */
    public function loadAllBankAccount($userId) {
        $datas = $this->getModel($userId)->where([
            'uid' => $userId
        ])->select()->toArray();
        $ret = [];
        foreach ($datas as $data) {
            $ret[$data['account']] = $data['count'];
        }
        return $ret;
    }

    public function loadBankAccount($userId, $account) {
        $data = $this->getModel($userId)->where([
            'uid' => $userId,
            'account' => $account
        ])->find();
        if (!empty($data)) {
            return $data['count'];
        }
        return 0;
    }

    public function createAccount($userId, $account) {
        return $this->getModel($userId)->insert([
            'uid' => $userId,
            'account' => $account,
            'count' => 0
        ]);
    }

    public function incAccount($userId, $account, $count) {
        assert($count >= 0);
        return $this->getModel($userId)->where([
            'uid' => $userId,
            'account' => $account
        ])->inc('count', $count)->update();
    }

    /**
     * @param $userId
     * @param $count
     * @return EnergyDao
     */
    public function decAccount($userId, $account, $count, $canNegative) {
        assert($count >= 0);
        if ($canNegative) {
            return $this->getModel($userId)->where([
                'uid' => $userId,
                'account' => $account
            ])->dec('count', $count)->update();
        }
        $whereStr = sprintf("uid=%d and account='%s' and count >= %d", $userId, $account, $count);
        return $this->getModel($userId)->whereRaw($whereStr)->dec('count', $count)->update();
    }
}


