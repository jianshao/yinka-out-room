<?php


namespace app\domain\asset;


use app\domain\exceptions\FQException;

class AssetKindVip extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = 'vip';
        $this->unit = '天';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getVip($timestamp)->addVip($count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        throw new FQException('接口未实现', 500);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getVip($timestamp)->vipBalance($timestamp);
    }
}