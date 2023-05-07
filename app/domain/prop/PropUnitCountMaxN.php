<?php

namespace app\domain\prop;
use app\domain\exceptions\AssetNotEnoughException;
use app\utils\ArrayUtil;

/**
 * 数量
 */
class PropUnitCountMaxN extends PropUnitCount
{
    public static $TYPE_NAME = 'countMaxN';
    public $maxN = 0;

    function add($prop, $count, $timestamp) {
        assert($count >= 0);
        $prop->count = min($prop->count + $count, $this->maxN);
    }

    function consume($prop, $count, $timestamp) {
        assert($count >= 0);
        if ($count > 0) {
            $balance = $this->balance($prop, $timestamp);
            if ($balance < $count) {
                throw new AssetNotEnoughException('背包数量不足', 500);
            }
            $prop->count -= $count;
        }
    }

    function balance($prop, $timestamp) {
        return $prop->count;
    }

    public function translateOld($prop, $timestamp) {
        $prop->expiresTime = 0;
        $prop->count = $this->maxN;
    }

    public function balanceByPropModel($propModel, $timestamp) {
        return $propModel->count;
    }

    public function isTiming() {
        return false;
    }

    protected function decodeFromJsonImpl($jsonObj) {
        $this->maxN = ArrayUtil::safeGet($jsonObj, 'maxN', 1);
    }
}


