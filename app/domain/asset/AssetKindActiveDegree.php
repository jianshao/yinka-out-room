<?php

namespace app\domain\asset;

// 金币资产
class AssetKindActiveDegree extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->unit = '个';
        $this->displayName = '活跃值';
        $this->image = '/image/exp.png';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getActiveDegree($timestamp)->add($count, $timestamp);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getActiveDegree($timestamp)->consume($count, $timestamp);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getActiveDegree($timestamp)->balance($timestamp);
    }
}
