<?php

namespace app\domain\user\model;
use app\utils\TimeUtil;

class ActiveDegreeModel
{
    public $day = 0;
    public $week = 0;
    public $updateTime = 0;

    public function __construct($day=0, $week=0, $updateTime=0) {
        $this->day = $day;
        $this->week = $week;
        $this->updateTime = $updateTime;
    }

    public function adjust($timestamp) {
        if (!TimeUtil::isSameWeek($timestamp, $this->updateTime)) {
            $this->week = 0;
            $this->day = 0;
            $this->updateTime = $timestamp;
        }

        if (!TimeUtil::isSameDay($timestamp, $this->updateTime)) {
            $this->day = 0;
            $this->updateTime = $timestamp;
        }
    }
}


