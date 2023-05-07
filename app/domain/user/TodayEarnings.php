<?php


namespace app\domain\user;


use app\domain\user\dao\TodayEarningsModelDao;
use app\domain\user\model\TodayEarningsModel;
use think\facade\Log;

class TodayEarnings
{
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
            Log::info(sprintf('TodayEarningsLoaded userId=%d diamond=%d', $this->getUserId(), $this->model->diamond));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function adjust($timestamp) {
        $this->model->adjust($timestamp);
    }

    public function addEarnings($count, $timestamp) {
        $this->model->add($count, $timestamp);
        TodayEarningsModelDao::getInstance()->saveTodayEarnings($this->getUserId(), $this->model);
    }

    public function getEarnings() {
        return $this->model->diamond;
    }

    private function doLoad($timestamp) {
        $model = TodayEarningsModelDao::getInstance()->loadTodayEarnings($this->getUserId());
        if ($model === null) {
            $model = new TodayEarningsModel(0, $timestamp);
            TodayEarningsModelDao::getInstance()->createTodayEarnings($this->getUserId(), $model);
        }
        $this->model = $model;
    }
}