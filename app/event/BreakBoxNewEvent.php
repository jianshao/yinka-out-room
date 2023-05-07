<?php

namespace app\event;


class BreakBoxNewEvent extends AppEvent
{
    // 砸蛋通知
    public $userId = 0;
    public $roomId = 0;
    //
    public $boxId = 0;
    // 开的次数
    public $count = 0;
    // 开箱子消耗了什么list<AssetItem>
    public $consumeAssetList = null;
    // 开箱子获得了礼物
    public $deliveryGiftMap = null;
    // 特殊礼物
    public $deliverySpecialGiftId = null;

    public function __construct($userId, $roomId, $boxId, $count, $consumeAssetList,
                                $deliveryGiftMap, $deliverySpecialGiftId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->boxId = $boxId;
        $this->count = $count;
        $this->roomId = $roomId;
        $this->consumeAssetList = $consumeAssetList;
        $this->deliveryGiftMap = $deliveryGiftMap;
        $this->deliverySpecialGiftId = $deliverySpecialGiftId;
    }
}