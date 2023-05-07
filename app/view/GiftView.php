<?php

namespace app\view;

use app\utils\CommonUtil;

class GiftView
{
    public static function encodeGiftWall($giftKind, $count) {
        return [
            'gift_id' => $giftKind->kindId,
            'gift_image' => CommonUtil::buildImageUrl($giftKind->image),
            'gift_name' => $giftKind->name,
            'pack_num' => $count
        ];
    }
}