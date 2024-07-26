<?php

namespace app\domain\asset;

// 豆资产
class AssetKindBean extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->unit = '个';
        $this->displayName = 'LB';
        $this->image = '/image/md.png';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getBean($timestamp)->add($count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getBean($timestamp)->consume($count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getBean($timestamp)->balance($timestamp);
    }
}

