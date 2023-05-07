<?php


namespace app\domain\gift;


class GiftUtils
{
    public static function calcTotalValue($giftMap) {
        $value = 0;
        foreach ($giftMap as $giftId => $count) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind != null) {
                $value += intval($giftKind->price->count * $count);
            }
        }
        return $value;
    }
}