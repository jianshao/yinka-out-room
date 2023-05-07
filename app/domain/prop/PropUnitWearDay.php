<?php

namespace app\domain\prop;

use app\domain\exceptions\AssetNotEnoughException;
use app\utils\TimeUtil;
use think\facade\Log;

/**
 * 佩戴天数
 */
class PropUnitWearDay extends PropUnit
{
    public static $TYPE_NAME = 'wearDay';

    public function add($prop, $count, $timestamp) {
        assert(is_integer($count) && $count >= 0);
        if ($count > 0) {
            $balance = $this->balance($prop, $timestamp);
            if ($balance <= 0) {
                $prop->count = 0;
                $prop->isWore = 0;
                $prop->woreTime = 0;
            }
            $prop->count += $count;
        }
    }

    public function consume($prop, $count, $timestamp) {
        assert(is_integer($count) && $count >= 0);
        if ($count > 0) {
            $balance = $this->balance($prop, $timestamp);
            if ($balance < $count) {
                throw new AssetNotEnoughException('背包数量不足', 500);
            }
            $prop->count -= $count;
        }
    }

    public function balance($prop, $timestamp) {
        return $this->balanceByPropModel($prop, $timestamp);
    }

    public function remainTime($prop, $timestamp){
        if (!$prop->isWore){
            if ($prop->woreTime <= 0) {
                // 没佩戴过
                $delta = $prop->count*86400;
            } else {
                // 佩戴过，如果是佩戴时间超过24小时
                if ($this->isWearDay($prop->woreTime, $timestamp)) {
                    $delta = ($prop->count-1)*86400;
                }else{
                    $delta = ($prop->count-1)*86400 + (86400-($timestamp-$prop->woreTime));
                }
            }
        }else{
            $delta = $prop->count*86400 - ($timestamp - $prop->woreTime);
        }

        return $delta;
    }

    function breakUpBalance($prop, $timestamp) {
        $delta = $this->remainTime($prop, $timestamp);
        return intval($delta / 86400.0);
    }

    public function buildDisplay($prop, $timestamp) {
        $delta = $this->remainTime($prop, $timestamp);
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

    public function translateOld($prop, $timestamp) {
        Log::info(sprintf('translateOld can not use'));
        return;

        $diffDays = TimeUtil::calcDiffDays($timestamp, $prop->expiresTime);

        $prop->count = 0;
        $prop->woreTime = 0;

        Log::info(sprintf('PropUnitWearDay::translateOld propId=%d kindId=%d expiresTime=%d diffDays=%d', $prop->propId,
            $prop->kind->kindId, $prop->expiresTime, $diffDays));

        $prop->expiresTime = 0;

        // 如果是佩戴中，剩余的count就是剩余的天数+1
        if ($prop->isWore) {
            $prop->woreTime = $timestamp;
            if ($diffDays >= 0) {
                $prop->count = $diffDays + 1;
            }
        } else {
            // 没有佩戴那就是剩余天数
            $prop->count = max(0, $diffDays);
        }
    }

    public function balanceByPropModel($prop, $timestamp) {
        if (!$prop->isWore) {
            // 没有佩戴
            if ($prop->woreTime <= 0) {
                // 没佩戴过
                return $prop->count;
            }
            // 佩戴过，如果是佩戴时间没有超过24小时那count不变
            if (!$this->isWearDay($prop->woreTime, $timestamp)) {
                return $prop->count;
            }
            // 不是当天佩戴的 - 1（佩戴或取消佩戴那天算1天）
            return max(0, $prop->count - 1);
        } else {
            // 佩戴中，如果是佩戴时间没有超过24小时，不减
            $diffDay = max(0, intval(($timestamp - $prop->woreTime) / 86400.0));
            if ($diffDay == 0) {
                return $prop->count;
            }
            return max(0, $prop->count - $diffDay);
        }
    }

    function isTiming() {
        return true;
    }

    public function processWear($prop, $timestamp) {
        if ($prop->woreTime <= 0){
            $prop->woreTime = $timestamp;
        }

        if ($prop->kind->unit->isWearDay($prop->woreTime, $timestamp)){
            # 如果穿戴的天数大于1 穿戴的时间重新计算
            $prop->count -= 1;
            $prop->woreTime = $timestamp;
        }
    }

    public function processUnwear($prop, $timestamp) {
        // 取消佩戴后需要重新计算数量
        $diffDay = max(0, intval(($timestamp - $prop->woreTime) / 86400.0));
        // 当天佩戴再卸下不需要减
        if ($diffDay > 0) {
            $prop->count = max(0, $prop->count - $diffDay);
            $prop->woreTime += $diffDay*86400;
        }
    }

    private function isWearDay($woreTime, $timestamp){
        //是否穿戴超过24小时了
        return $timestamp - $woreTime >= 86400;
    }
}


