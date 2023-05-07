<?php

namespace app\domain\bank;

use app\domain\asset\AssetUtils;
use app\domain\bank\dao\BankAccountDao;
use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException2;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use think\facade\Log;

class Bank
{
    private $user = null;
    // map<account, count>
    private $accountMap = null;

    private $_isLoaded = false;

    public function __construct($user) {
        $this->user = $user;
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function isLoaded() {
        return $this->_isLoaded;
    }

    public function load($timestamp) {
        if (!$this->_isLoaded) {
            $this->doLoad($timestamp);
            $this->_isLoaded = true;
            // TODO log
        }
    }

    /**
     * 给account类型的账户增加count个数量
     * 
     * @param account: 账户
     * @param count: 数量
     * @param timestamp: 时间戳
     * @param biEvent: 事件
     * @return: 余额
     */
    public function add($account, $count, $timestamp, $biEvent) {
        assert($count >= 0);

        $accountType = BankSystem::getInstance()->findAccountType($account);
        if ($accountType == null) {
            throw new FQException('不能识别的资产类型', 500);
        }

        if (!array_key_exists($account, $this->accountMap)) {
            BankAccountDao::getInstance()->createAccount($this->getUserId(), $account);
            $this->accountMap[$account] = 0;
        }

        BankAccountDao::getInstance()->incAccount($this->getUserId(), $account, $count);

        $balance = $this->accountMap[$account] + $count;

        BIReport::getInstance()->reportBank($this->getUserId(), $account, $count, $balance, $timestamp, $biEvent);

        $this->accountMap[$account] = $balance;

        return $balance;
    }

    /**
     * 给account类型账户消耗count个数量
     * 
     * @param typeId: 账户类型
     * @param count: 数量
     * @param timestamp: 时间戳
     * @param biEvent: 事件
     * 
     * @return: 余额
     */
    public function consume($account, $count, $timestamp, $biEvent) {
        assert($count >= 0);

        $accountType = BankSystem::getInstance()->findAccountType($account);
        if ($accountType == null) {
            throw new FQException('不能识别的资产类型', 500);
        }

        if (!array_key_exists($account, $this->accountMap)) {
            if (!$accountType->canNegative) {
                throw new AssetNotEnoughException2(AssetUtils::makeBankAssetId($account), '余额不足', 211);
            }
            BankAccountDao::getInstance()->createAccount($this->getUserId(), $account);
            $this->accountMap[$account] = 0;
        }

        $balance = $this->accountMap[$account] - $count;

        if (!$accountType->canNegative && $balance < 0) {
            throw new AssetNotEnoughException2(AssetUtils::makeBankAssetId($account), '余额不足', 211);
        }

        if (!BankAccountDao::getInstance()->decAccount($this->getUserId(), $account, $count, $accountType->canNegative)) {
            Log::warning(sprintf('Bank::consume userId=%d account=%s count=%d balance=%d',
                $this->getUserId(), $account, $count, $this->accountMap[$account]));
            throw new AssetNotEnoughException2('余额不足', 500);
        }

        BIReport::getInstance()->reportBank($this->getUserId(), $account, -$count, $balance, $timestamp, $biEvent);

        $this->accountMap[$account] = $balance;

        return $balance;
    }

    /**
     * 查询typeId账户的余额
     * 
     * @param typeId: 账户类型
     * 
     * @return: 余额
     */
    public function balance($account, $timestamp) {
        return ArrayUtil::safeGet($this->accountMap, $account, 0);
    }

    private function doLoad($timestamp) {
        $loadAccountMap = BankAccountDao::getInstance()->loadAllBankAccount($this->getUserId());
        $accountMap = [];
        foreach ($loadAccountMap as $account => $count) {
            $accountType = BankSystem::getInstance()->findAccountType($account);
            if ($accountType != null) {
                $accountMap[$account] = $count;
            } else {
                Log::warning(sprintf('Bank::doLoad userId=%d UnknownAccount account=%s count=%d', $this->getUserId(), $account, $count));
            }
        }
        $this->accountMap = $accountMap;
    }
}


