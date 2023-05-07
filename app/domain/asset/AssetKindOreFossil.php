<?php


namespace app\domain\asset;


use app\domain\game\taojin\OreTypes;

class AssetKindOreFossil extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = '化石';
        $this->unit = '块';
        $this->image = '/gift/20201109/0bd18c459dc152d4e192bbf1f679d1e1.png';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->addOre(OreTypes::$FOSSIL, $count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getOre($timestamp)->consumeOre(OreTypes::$FOSSIL, $count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getOre($timestamp)->balanceOre(OreTypes::$FOSSIL, $timestamp);
    }
}