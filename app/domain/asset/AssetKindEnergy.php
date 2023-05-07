<?php


namespace app\domain\asset;


class AssetKindEnergy extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = '体力';
        $this->unit = '个';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getEnergy($timestamp)->add($count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getEnergy($timestamp)->consume($count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getEnergy($timestamp)->balance($timestamp);
    }
}