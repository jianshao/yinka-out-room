<?php

namespace app\view;

use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class GiftCollectionView
{
    public static function encodeGiftCollection($gift, $count) {
        $giftKind = ArrayUtil::safeGet($gift,'gift');
        $collection = ArrayUtil::safeGet($gift,'giftCollection');
        return [
            'giftId' => $giftKind->kindId,
            'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
            'giftName' => $giftKind->name,
            'giftPrice' => $giftKind->price ? $giftKind->price->count : 0,
            'giftCount' => $count,
            'collectionDesc' => $collection->collectionDesc,
            'collectionImage' => CommonUtil::buildImageUrl($collection->collectionImage),
        ];
    }
}