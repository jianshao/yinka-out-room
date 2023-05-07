<?php


namespace app\domain\user\model;


use app\utils\TimeUtil;

class TodayEarningsModel
{
    // 今日钻石收益
    public $diamond = 0;
    // 更新日期
    public $updateTime = 0;

    public function __construct($diamond=0, $updateTime=0) {
        $this->diamond = $diamond;
        $this->updateTime = $updateTime;
    }

    public function adjust($timestamp) {
        if (!TimeUtil::isSameDay($timestamp, $this->updateTime)) {
            $this->diamond = 0;
            $this->updateTime = $timestamp;
            return true;
        }
        return false;
    }

    public function add($count, $timestamp) {
        assert($count >= 0);
        $this->adjust($timestamp);
        $this->diamond += $count;
        $this->updateTime = $timestamp;
        return $this;
    }
}