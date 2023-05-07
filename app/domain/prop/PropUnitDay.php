<?php

namespace app\domain\prop;

use app\domain\exceptions\AssetNotEnoughException;
use app\utils\TimeUtil;
use think\facade\Log;

/**
 * 自然天
 */
class PropUnitDay extends PropUnit
{
    public static $TYPE_NAME = 'day';

    function add($prop, $count, $timestamp) {
        assert(is_integer($count) && $count >= 0);
        if ($count > 0) {
            if ($prop->expiresTime <= 0 || $prop->expiresTime <= $timestamp) {
                $prop->expiresTime = $timestamp;
            }
//            $prop->expiresTime = TimeUtil::calcDayStartTimestamp($prop->expiresTime + $count * 86400);
            Log::info(sprintf('PropUnitDay::add propId=%d kindId=%d expiresTime=%d count=%d addtime=%d',
                $prop->propId, $prop->kind->kindId, $prop->expiresTime, $count, $count * 86400));
            $prop->expiresTime = $prop->expiresTime + $count * 86400;
        }
    }

    function consume($prop, $count, $timestamp) {
        assert(is_integer($count) && $count >= 0);
        if ($count > 0) {
            $balance = $this->balance($prop, $timestamp);
            if ($balance < $count) {
                throw new AssetNotEnoughException('背包数量不足', 500);
            }
            $prop->expiresTime -= $count * 86400;
        }
    }

    function balance($prop, $timestamp) {
        return $this->balanceByPropModel($prop, $timestamp);
    }

    public function translateOld($prop, $timestamp) {
        Log::info(sprintf('translateOld can not use'));
        return;
        $prop->expiresTime = TimeUtil::calcDayStartTimestamp($prop->expiresTime);
        $prop->count = 0;
        $prop->woreTime = 0;
    }

    public function balanceByPropModel($prop, $timestamp) {
        if ($prop->expiresTime <= $timestamp) {
            return 0;
        }
        return intval(ceil(($prop->expiresTime - $timestamp) / 86400.0));
    }

    function isTiming() {
        return true;
    }

    function breakUpBalance($prop, $timestamp) {
        if ($prop->expiresTime <= $timestamp) {
            return 0;
        }
        return intval(floor(($prop->expiresTime - $timestamp) / 86400.0));
    }

    public function buildDisplay($prop, $timestamp) {
        if ($prop->expiresTime <= 0) {
            return '永久';
        } else {
            $delta = max(0, $prop->expiresTime - $timestamp);
            if ($delta <= 0) {
                return '已过期';
            } elseif ($delta < 86400) {
                return '即将过期';
            } elseif ($delta >= 3*365*86400) {
                # 大于3年的都是永久
                return '永久';
            } else {
                return $this->transTimeStr($delta);
            }
        }
    }
}


