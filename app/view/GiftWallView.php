<?php

namespace app\view;

use app\utils\CommonUtil;

class GiftWallView
{
    public static function encodeGiftWall($giftKind, $count) {
        return [
            'giftId' => $giftKind->kindId,
            'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
            'giftName' => $giftKind->name,
            'giftPrice' => $giftKind->price ? $giftKind->price->count : 0,
            'giftCount' => $count,
        ];
    }
}