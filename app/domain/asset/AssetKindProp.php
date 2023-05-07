<?php

namespace app\domain\asset;

// 道具
class AssetKindProp extends AssetKind
{
    public $propKind = null;

    public function __construct($propKind) {
        $this->kindId = AssetUtils::makePropAssetId($propKind->kindId);
        $this->unit = $propKind->unit->displayName;
        $this->propKind = $propKind;
        $this->displayName = $propKind->name;
        $this->image = $propKind->image;
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getPropBag($timestamp)->addPropByUnit($this->propKind->kindId, $count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getPropBag($timestamp)->consumePropByUnit($this->propKind->kindId, $count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getPropBag($timestamp)->balance($this->propKind->kindId, $timestamp);
    }
}