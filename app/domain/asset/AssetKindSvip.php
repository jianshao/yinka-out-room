<?php


namespace app\domain\asset;


use app\domain\exceptions\FQException;

class AssetKindSvip extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = 'svip';
        $this->unit = '天';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getVip($timestamp)->addSvip($count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        throw new FQException('接口未实现', 500);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getVip($timestamp)->svipBalance($timestamp);
    }
}