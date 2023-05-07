<?php


namespace app\domain\activity\guoqing;


use app\domain\gift\GiftUtils;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class GuoQingUser
{
    // 用户ID
    public $userId = 0;
    // 能量
//    public $energy = null;
    // 动态奖励状态 0-领取 1-已领取
    public $forumStatus = 0;
    // 已领取的宝箱 [宝箱id]
    public $boxs = [];
    public $updateTime = 0;


    public function __construct($userId, $timestamp=0) {
        $this->userId = $userId;
        $this->updateTime = $timestamp;
    }

    public function adjust($timestamp) {
        if (TimeUtil::isSameDay($this->updateTime, $timestamp)) {
//            $this->energy = 0;
        }
    }

    public function fromJson($jsonObj, $timestamp) {
        $this->updateTime = $jsonObj['updateTime'];
//        $this->energy = ArrayUtil::safeGet($jsonObj, 'energy', 0);
        $this->forumStatus = ArrayUtil::safeGet($jsonObj, 'forumStatus', 0);
        $this->boxs = ArrayUtil::safeGet($jsonObj, 'boxs', []);

        $this->adjust($timestamp);

        return $this;
    }

    public function toJson() {
        return [
            'updateTime' => $this->updateTime,
//            'energy' => $this->energy,
            'forumStatus' => $this->forumStatus,
            'boxs' => $this->boxs
        ];
    }
}