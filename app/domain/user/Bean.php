<?php

namespace app\domain\user;

use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\user\dao\BeanModelDao;
use app\domain\exceptions\FQException;
use think\facade\Log;

class Bean
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
            Log::info(sprintf('BeanLoaded userId=%d total=%d free=%d balance=%d',
                $this->getUserId(), $this->model->total, $this->model->free, $this->balance($timestamp)));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function add($count, $timestamp, $biEvent) {
        assert($count >= 0);
        if ($count > 0) {
            BeanModelDao::getInstance()->incTotal($this->getUserId(), $count);
            $this->model->total += $count;

            BIReport::getInstance()->reportBean($this->getUserId(), $count, $this->model->balance(), $timestamp, $biEvent);
            Log::info(sprintf('BeanAddOk userId=%d count=%d total=%d free=%d balance=%d',
                        $this->getUserId(), $count, $this->model->total, $this->model->free, $this->balance($timestamp)));
            // TODO event
        }
        return $this->balance($timestamp);
    }

    public function consume($count, $timestamp, $biEvent) {
        assert($count >= 0);
        if ($count > 0) {
            if ($this->balance($timestamp) < $count) {
                throw new AssetNotEnoughException('LB数量不足', 500);
            }
            if (!BeanModelDao::getInstance()->incFree($this->getUserId(), $count)) {
                Log::warning(sprintf('BeanConsumeNotEnough userId=%d count=%d total=%d free=%d balance=%d',
                    $this->getUserId(), $count, $this->model->total, $this->model->free, $this->balance($timestamp)));
                throw new AssetNotEnoughException('LB数量不足', 500);
            }
            $this->model->free += $count;

            BIReport::getInstance()->reportBean($this->getUserId(), -$count, $this->model->balance(), $timestamp, $biEvent);

            Log::info(sprintf('BeanConsumeOk userId=%d count=%d total=%d free=%d balance=%d',
                $this->getUserId(), $count, $this->model->total,
                $this->model->free, $this->balance($timestamp)));
        }

        // TODO event
        return $this->balance($timestamp);
    }

    public function balance($timestamp) {
        return $this->model->balance();
    }

    private function doLoad($timestamp) {
        $model = BeanModelDao::getInstance()->loadBean($this->getUserId());
        if ($model == null) {
            throw new FQException('用户豆数据不存在', 500);
        }
        $this->model = $model;
    }
}


