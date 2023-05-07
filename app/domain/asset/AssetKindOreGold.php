<?php


namespace app\domain\asset;


use app\domain\game\taojin\OreTypes;

class AssetKindOreGold extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = '金矿石';
        $this->unit = '块';
        $this->image = '/gift/20201109/d8e521b9a25a7aae27924411512f7ef2.png';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->addOre(OreTypes::$GOLD, $count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->consumeOre(OreTypes::$GOLD, $count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getOre($timestamp)->balanceOre(OreTypes::$GOLD, $timestamp);
    }
}