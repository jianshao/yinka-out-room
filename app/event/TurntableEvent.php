<?php

namespace app\event;


class TurntableEvent extends AppEvent
{
    // 转盘通知
    public $userId = 0;
    public $roomId = 0;
    //
    public $boxId = 0;
    // 开的次数
    public $count = 0;
    // 转盘消耗了什么list<AssetItem>
    public $consumeAssetList = null;
    // 转盘获得了礼物
    public $deliveryGiftMap = null;

    public function __construct($userId, $roomId, $boxId, $count, $consumeAssetList,
                                $deliveryGiftMap, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->boxId = $boxId;
        $this->count = $count;
        $this->roomId = $roomId;
        $this->consumeAssetList = $consumeAssetList;
        $this->deliveryGiftMap = $deliveryGiftMap;
    }
}