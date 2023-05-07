<?php

namespace app\domain\user;

use app\domain\bi\BIReport;
use app\domain\user\dao\CoinDao;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use think\facade\Log;

class Coin
{
    private $user = null;
    private $count = 0;
    private $_isLoaded = false;

    public function  __construct($user) {
        $this->user = $user;
    }

    /**
     * 是否加载了
     */
    public function isLoaded() {
        return $this->_isLoaded;
    }

    /**
     * 加载用户背包
     */
    public function load($timestamp) {
        if (!$this->isLoaded()) {
            $this->doLoad($timestamp);
            $this->_isLoaded = true;
            Log::info(sprintf('CoinLoaded userId=%d balance=%d', $this->getUserId(), $this->balance($timestamp)));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function add($count, $timestamp, $biEvent) {
        assert($count >= 0);
        if ($count > 0) {
            $this->count += $count;
            CoinDao::getInstance()->incCoin($this->getUserId(), $count);

            BIReport::getInstance()->reportCoin($this->getUserId(), $count, $this->count, $timestamp, $biEvent);

            Log::info(sprintf('CoinAddOk userId=%d count=%d balance=%d',
                    $this->getUserId(), $count, $this->balance($timestamp)));
            // TODO event
        }
        return $this->balance($timestamp);
    }

    public function consume($count, $timestamp, $biEvent) {
        assert($count >= 0);
        if ($count > 0) {
            if ($this->balance($timestamp) < $count) {
                throw new AssetNotEnoughException('金币数量不足', 500);
            }

            if (!CoinDao::getInstance()->decCoin($this->getUserId(), $count)) {
                Log::warning(sprintf('CoinConsumeNotEnough userId=%d count=%d balance=%d',
                    $this->getUserId(), $count, $this->balance($timestamp)));
                throw new AssetNotEnoughException('金币数量不足', 500);
            }
            $this->count -= $count;
            BIReport::getInstance()->reportCoin($this->getUserId(), -$count, $this->count, $timestamp, $biEvent);
            Log::info(sprintf('CoinConsumeOk userId=%d count=%d balance=%d',
                $this->getUserId(), $count, $this->balance($timestamp)));

            // TODO event
        }
        return $this->balance($timestamp);
    }

    public function balance($timestamp) {
        return $this->count;
    }

    private function doLoad($timestamp) {
        $count = CoinDao::getInstance()->loadCoin($this->getUserId());
        if ($count === null) {
            throw new FQException('用户金币数据不存在', 500);
        }
        $this->count = $count;
    }
}


