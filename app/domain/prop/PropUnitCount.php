<?php

namespace app\domain\prop;
use app\domain\exceptions\AssetNotEnoughException;


/**
 * 数量
 */
class PropUnitCount extends PropUnit
{
    public static $TYPE_NAME = 'count';

    function add($prop, $count, $timestamp) {
        assert($count >= 0);
        $prop->count += $count;
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
    }

    public function balanceByPropModel($propModel, $timestamp) {
        return $propModel->count;
    }

    public function isTiming() {
        return false;
    }

    public function buildDisplay($prop, $timestamp) {
        $balance = $this->balance($prop, $timestamp);
        if ($balance < 1) {
            return '已过期';
        }
        return $balance . $this->displayName;
    }
}


