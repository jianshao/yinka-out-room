<?php

namespace app\domain\asset;

// 金币资产
class AssetKindCoin extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = '金币';
        $this->unit = '个';
        $this->image = '/download/jinbitubiao20220326.png';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getCoin($timestamp)->add($count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getCoin($timestamp)->consume($count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getCoin($timestamp)->balance($timestamp);
    }
}
