<?php


namespace app\domain\asset;


use app\domain\game\taojin\OreTypes;

class AssetKindOreIron extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = '铁矿石';
        $this->unit = '块';
        $this->image = '/gift/20201109/efe00d2a8687b6d726aee4dc8fe0f782.png';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->addOre(OreTypes::$IRON, $count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->consumeOre(OreTypes::$IRON, $count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getOre($timestamp)->balanceOre(OreTypes::$IRON, $timestamp);
    }
}