<?php


namespace app\domain\activity\zhongqiuPK;


use app\domain\gift\GiftUtils;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class ZhongQiuPKUser
{
    // 用户ID
    public $userId = 0;
    // 帮派
    public $faction = null;
    // 签到奖励状态 0-不可领取 1-可领取 2-已领取
    public $checkinStatus = 0;
    // 已签到的日子 [1:1] [签到日子，2-已签 3-补签]
    public $checkins = [];
    public $updateTime = 0;


    public function __construct($userId, $timestamp=0) {
        $this->userId = $userId;
        $this->updateTime = $timestamp;
    }

    public function adjust($timestamp) {
        if (TimeUtil::isSameDay($this->updateTime, $timestamp)) {
//            $this->moonlightValue = 0;
        }
    }

    public function fromJson($jsonObj, $timestamp) {
        $this->updateTime = $jsonObj['updateTime'];
        $this->checkinStatus = ArrayUtil::safeGet($jsonObj, 'checkinStatus', 0);
        $this->faction = ArrayUtil::safeGet($jsonObj, 'faction', '');
        $this->checkins = ArrayUtil::safeGet($jsonObj, 'checkins', []);

        $this->adjust($timestamp);

        return $this;
    }

    public function toJson() {
        return [
            'updateTime' => $this->updateTime,
            'checkinStatus' => $this->checkinStatus,
            'faction' => $this->faction,
            'checkins' => $this->checkins
        ];
    }
}