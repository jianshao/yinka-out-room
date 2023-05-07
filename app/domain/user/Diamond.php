<?php

namespace app\domain\user;
use app\domain\bi\BIReport;
use app\domain\user\dao\DiamondModelDao;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use think\facade\Log;

class Diamond
{
    private $user = null;
    private $model = null;
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
            Log::info(sprintf('DiamondLoaded userId=%d total=%d free=%d exchange=%d balance=%d',
                    $this->getUserId(), $this->model->total,
                    $this->model->free, $this->model->exchange,
                    $this->balance($timestamp)));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function getTotal() {
        return $this->model->total;
    }

    public function getFree() {
        return $this->model->free;
    }

    public function getExchange() {
        return $this->model->exchange;
    }

    public function add($count, $timestamp, $biEvent) {
//        assert($count >= 0);
        if ($count > 0) {
            DiamondModelDao::getInstance()->incTotal($this->getUserId(), $count);
            $this->model->total += $count;
        }

        BIReport::getInstance()->reportDiamond($this->getUserId(), $count, $this->model->balance(), $timestamp, $biEvent);

        Log::info(sprintf('DiamondAddOk userId=%d count=%d total=%d free=%d exchange=%d balance=%d',
                $this->getUserId(), $count, $this->model->total,
                $this->model->free, $this->model->exchange,
                $this->balance($timestamp)));

        return $this->balance($timestamp);
    }

    public function consume($count, $timestamp, $biEvent) {
//        assert($count >= 0);
        if ($count > 0) {
            if ($this->balance($timestamp) < $count) {
                throw new AssetNotEnoughException('钻石数量不足', 500);
            }
            if (!DiamondModelDao::getInstance()->incFree($this->getUserId(), $count)) {
                Log::warning(sprintf('DiamondConsumeNotEnough userId=%d count=%d total=%d free=%d exchange=%d balance=%d',
                    $this->getUserId(), $count, $this->model->total,
                    $this->model->free, $this->model->exchange,
                    $this->balance($timestamp)));
                throw new AssetNotEnoughException('钻石数量不足', 500);
            }
            $this->model->free += $count;
        }

        BIReport::getInstance()->reportDiamond($this->getUserId(), -$count, $this->model->balance(), $timestamp, $biEvent);

        Log::info(sprintf('DiamondConsumeOk userId=%d total=%d free=%d exchange=%d balance=%d',
                $this->getUserId(), $this->model->total,
                $this->model->free, $this->model->exchange,
                $this->balance($timestamp)));

        return $this->balance($timestamp);
    }

    public function exchange($count, $timestamp, $biEvent) {
        assert($count >= 0);
        if ($count > 0) {
            if ($this->balance($timestamp) < $count) {
                throw new AssetNotEnoughException('钻石数量不足', 500);
            }
            if (!DiamondModelDao::getInstance()->incExchange($this->getUserId(), $count)) {
                Log::warning(sprintf('DiamondExchangeNotEnough userId=%d count=%d total=%d free=%d exchange=%d balance=%d',
                    $this->getUserId(), $count, $this->model->total,
                    $this->model->free, $this->model->exchange,
                    $this->balance($timestamp)));
                throw new AssetNotEnoughException('钻石数量不足', 500);
            }

            $this->model->exchange += $count;

            BIReport::getInstance()->reportDiamond($this->getUserId(), -$count, $this->model->balance(), $timestamp, $biEvent);

            Log::info(sprintf('DiamondExchangeOk userId=%d count=%d total=%d free=%d exchange=%d balance=%d',
                $this->getUserId(), $count, $this->model->total,
                $this->model->free, $this->model->exchange,
                $this->balance($timestamp)));
            // TODO event
        }
        return $this->balance($timestamp);
    }

    public function balance($timestamp) {
        return $this->model->balance();
    }

    private function doLoad($timestamp) {
        $model = DiamondModelDao::getInstance()->loadDiamond($this->getUserId());
        if ($model == null) {
            throw new FQException('用户钻石数据不存在', 500);
        }
        $this->model = $model;
    }
}


