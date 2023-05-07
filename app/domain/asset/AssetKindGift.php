<?php

namespace app\domain\asset;

class AssetKindGift extends AssetKind
{
    public $giftKind = null;

    public function __construct($giftKind) {
        $this->kindId = AssetUtils::makeGiftAssetId($giftKind->kindId);
        $this->unit = $giftKind->unit;
        $this->displayName = $giftKind->name;
        $this->image = $giftKind->image;
        $this->giftKind = $giftKind;
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getGiftBag($timestamp)->add($this->giftKind->kindId, $count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getGiftBag($timestamp)->consume($this->giftKind->kindId, $count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getGiftBag($timestamp)->balance($this->giftKind->kindId, $timestamp);
    }
}
