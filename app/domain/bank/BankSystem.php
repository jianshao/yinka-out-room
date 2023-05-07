<?php

namespace app\domain\bank;

use app\utils\ArrayUtil;

class BankSystem
{
    // 单例
    protected static $instance;
    // map<typeId, BankAccountType>
    private $accountTypeMap = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BankSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    /**
     * 根据账号类型Id查找账号类型
     *
     * @param typeId: 账号类型ID
     * @return: 找到返回BankAccountType，没找到返回null
     */
    public function findAccountType($account) {
        return $this->accountTypeMap[$account];
    }

    /**
     * 获取所有账户类型
     *
     * @return: map<typeId, BankAccountType>
     */
    public function getAccountTypeMap() {
        return $this->accountTypeMap;
    }

    private function loadFromJson() {
        $BANK_CONF = '[
            {
                "typeId":"game:candy",
                "displayName":"水球",
                "image":"/20211025/45da97c12064b733be2e15dec381453c.png",
                "canNegative":0
            },
            {
                "typeId":"game:score",
                "image":"resource/images/jifen2.png",
                "canNegative":0
            },
            {
                "typeId":"game:gashapon",
                "displayName":"扭蛋券",
                "canNegative":0
            },
            {
                "typeId":"chip:silver",
                "displayName":"银碎片",
                "canNegative":0
            },
            {
                "typeId":"chip:gold",
                "displayName":"金碎片",
                "canNegative":0
            }
        ]';
        $accountTypeMap = [];
        $bankAccountConfList = json_decode($BANK_CONF, true);
        foreach($bankAccountConfList as $bankAccountConf) {
            $type = new BankAccountType();
            $type->typeId = $bankAccountConf['typeId'];
            $type->canNegative = $bankAccountConf['canNegative'] == 1;
            $type->displayName = ArrayUtil::safeGet($bankAccountConf, 'displayName', '');
            $type->image = ArrayUtil::safeGet($bankAccountConf, 'image', '');
            $accountTypeMap[$type->typeId] = $type;
        }
        $this->accountTypeMap = $accountTypeMap;
    }
}