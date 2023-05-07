<?php


namespace app\domain\activity\giftReturn;


use app\domain\gift\GiftUtils;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class GiftReturnUser
{
    // 用户ID
    public $userId = 0;
    // 今日礼物（礼物id => 福袋数量）
    public $todayGiftMap = [];
    // 所有礼物（礼物id => 福袋数量）
    public $totalGiftMap = [];
    // 可领取豆的数量
    public $beanCount = 0;
    // 是否领取 0-不可领取 1-可领取 2-已领取
    public $gotReward = 0;
    public $updateTime = 0;


    public function __construct($userId, $timestamp=0) {
        $this->userId = $userId;
        $this->updateTime = $timestamp;
    }

    public function adjust($timestamp) {
        $diffDays = TimeUtil::calcDiffDays($this->updateTime, $timestamp);
        if ($diffDays > 0) {
            $this->updateTime = $timestamp;
            $this->beanCount = $diffDays > 1 ? 0 : GiftUtils::calcTotalValue($this->todayGiftMap);
            $this->gotReward = $this->beanCount > 0 ? 1 : 0;
            $this->todayGiftMap = [];
        }
    }

    public function fromJson($jsonObj, $timestamp) {
        $this->updateTime = intval(ArrayUtil::safeGet($jsonObj, 'updateTime', 0));
        $this->totalGiftMap = ArrayUtil::safeGet($jsonObj, 'totalGiftMap', []);
        $this->todayGiftMap = ArrayUtil::safeGet($jsonObj, 'todayGiftMap', []);
        $this->beanCount = ArrayUtil::safeGet($jsonObj, 'beanCount', 0);
        $this->gotReward = ArrayUtil::safeGet($jsonObj, 'gotReward', 0);

        $this->adjust($timestamp);

        return $this;
    }

    public function toJson() {
        return [
            'updateTime' => $this->updateTime,
            'gotReward' => $this->gotReward,
            'todayGiftMap' => $this->todayGiftMap,
            'totalGiftMap' => $this->totalGiftMap,
            'beanCount' => $this->beanCount,
        ];
    }
}