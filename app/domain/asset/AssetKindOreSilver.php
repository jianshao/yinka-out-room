<?php


namespace app\domain\asset;


use app\domain\game\taojin\OreTypes;

class AssetKindOreSilver extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = '银矿石';
        $this->unit = '块';
        $this->image = '/gift/20201109/10bb66b4ddadbbc2354e17c17c5de69a.png';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->addOre(OreTypes::$SILVER, $count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->consumeOre(OreTypes::$SILVER, $count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getOre($timestamp)->balanceOre(OreTypes::$SILVER, $timestamp);
    }
}