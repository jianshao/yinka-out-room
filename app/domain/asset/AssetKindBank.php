<?php

namespace app\domain\asset;

// 银行资产
class AssetKindBank extends AssetKind
{
    public $bankAccountType = null;

    public function __construct($bankAccountType) {
        $this->kindId = AssetUtils::makeBankAssetId($bankAccountType->typeId);
        $this->unit = $bankAccountType->unit;
        $this->bankAccountType = $bankAccountType;
        $this->displayName = $bankAccountType->displayName;
        $this->image = $bankAccountType->image;
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getBank($timestamp)->add($this->bankAccountType->typeId, $count, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        return $userAssets->getBank($timestamp)->consume($this->bankAccountType->typeId, $count, $timestamp, $biEvent);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getBank($timestamp)->balance($this->bankAccountType->typeId, $timestamp);
    }
}
