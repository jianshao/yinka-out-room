<?php

namespace app\view;

use app\utils\CommonUtil;

class EmoticonView
{
    public static function encodeEmoticon($emoticon) {
        return [
            'face_id' => $emoticon->emoticonId,
            'face_image' => CommonUtil::buildImageUrl($emoticon->image),
            'face_name' => $emoticon->name,
            'type' => $emoticon->type,
            'is_lock' => $emoticon->isLock,
            'animation' => CommonUtil::buildImageUrl($emoticon->animation),
            'is_vip' => $emoticon->vipLevel,
            'game_images' => $emoticon->gameImageList,
        ];
    }
}