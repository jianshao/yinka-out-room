<?php

namespace app\domain\gift;

use app\utils\ArrayUtil;

class GiftWall
{
    public $name = '';
    public $displayName = '';
    public $giftIds = null;
    public $gifts = null;

    public function decodeFromJson($jsonObj) {
        $this->name = $jsonObj['name'];
        $this->displayName = $jsonObj['displayName'];
        $this->giftIds = $jsonObj['gifts'];
    }

    public function initByGiftMap($giftMap) {
        $this->gifts = [];
        foreach ($this->giftIds as $giftId) {
            $gift = ArrayUtil::safeGet($giftMap, $giftId);
            if ($gift != null) {
                $this->gifts[] = $gift;
            }
        }
    }
}