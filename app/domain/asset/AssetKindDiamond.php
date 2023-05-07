<?php

namespace app\domain\asset;

// 钻石资产
class AssetKindDiamond extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = '钻石';
        $this->unit = '个';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getDiamond($timestamp)->add($count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getDiamond($timestamp)->consume($count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getDiamond($timestamp)->balance($timestamp);
    }
}