<?php

namespace app\domain\user;

use app\domain\user\dao\ActiveDegreeModelDao;
use app\domain\user\model\ActiveDegreeModel;
use think\facade\Log;

class ActiveDegree
{
    private $user = null;
    private $model = null;
    private $_isLoaded = false;

    public function __construct($user) {
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
            Log::info(sprintf('ActiveDegree load userId=%d day=%d week=%d updateTime=%d',
                $this->getUserId(), $this->model->day, $this->model->week, $this->model->updateTime));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function getDayValue() {
        return $this->model->day;
    }

    public function getWeekValue() {
        return $this->model->week;
    }

    public function add($value, $timestamp) {
        assert($value >= 0);

        if ($value > 0) {
            $this->model->adjust($timestamp);
            $this->model->day += $value;
            $this->model->week += $value;
            ActiveDegreeModelDao::getInstance()->saveActiveDegree($this->getUserId(), $this->model);
            Log::info(sprintf('ActiveDegree addok userId=%d count=%d day=%d week=%d updateTime=%d',
                $this->getUserId(), $value, $this->model->day, $this->model->week, $this->model->updateTime));
        }
    }

    public function consume($count, $timestamp) {
        return $this->balance($timestamp);
    }

    public function balance($timestamp) {
        return 0;
    }

    private function doLoad($timestamp) {
        $model = ActiveDegreeModelDao::getInstance()->loadActiveDegree($this->getUserId());
        if ($model == null) {
            $model = new ActiveDegreeModel(0, 0, $timestamp);
            // 此处不保存，有变化再保存
        } else {
            $model->adjust($timestamp);
        }
        $this->model = $model;
    }
}


