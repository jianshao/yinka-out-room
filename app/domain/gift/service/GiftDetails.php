<?php

namespace app\domain\gift\service;

class GiftDetails
{
    public $giftKind = null;
    public $deliveryGiftKind = null;
    public $consumeAsset = null;
    public $senderAssets = null;
    public $receiverAssets = null;
    public $deliveryCharm = 0;
    public $count = 0;

    public function __construct($giftKind, $deliveryGiftKind, $consumeAsset, $senderAssets, $receiverAssets, $deliveryCharm, $count) {
        $this->giftKind = $giftKind;
        $this->deliveryGiftKind = $deliveryGiftKind;
        $this->consumeAsset = $consumeAsset;
        $this->senderAssets = $senderAssets;
        $this->receiverAssets = $receiverAssets;
        $this->deliveryCharm = $deliveryCharm;
        $this->count = $count;
    }
}


