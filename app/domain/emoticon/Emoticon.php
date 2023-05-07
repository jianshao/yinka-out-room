<?php

namespace app\domain\emoticon;

use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\facade\Log;

class Emoticon
{
    public $emoticonId = 0;
    public $name = '';
    public $image = '';
    public $animation = '';
    public $gameImageList = null;
    // VIP级别
    public $vipLevel = 0;
    public $isLock = 0;
    public $type = 0;

    public function decodeFromJson($jsonObj) {
        $this->emoticonId = $jsonObj['id'];
        $this->name = $jsonObj['name'];
        $this->image = $jsonObj['image'];
        $this->animation = $jsonObj['animation'];
        $this->isLock = $jsonObj['isLock'];
        $this->type = ArrayUtil::safeGet($jsonObj, 'type', 0);
        $this->vipLevel = ArrayUtil::safeGet($jsonObj, 'vipLevel', 0);
        $gameImageList = ArrayUtil::safeGet($jsonObj, 'gameImages');
        $this->gameImageList = [];
        if (!empty($gameImageList)) {
            foreach ($gameImageList as $gameImage) {
                if (!is_string($gameImage)) {
                    Log::warning(sprintf('EmoticonBadGameImages emoticonId=%d',
                        $this->emoticonId));
                } else {
                    $gameImage = trim($gameImage);
                    if (!empty($gameImage)) {
                        $this->gameImageList[] = CommonUtil::buildImageUrl($gameImage);
                    }
                }
            }
        }
    }
}