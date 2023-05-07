<?php

namespace app\domain\gift;

use app\utils\ArrayUtil;

class GiftCollections
{
    public $displayName = '';
    public $giftIds = null;
    public $gifts = null;

    public function decodeFromJson($jsonObj) {
        $this->displayName = $jsonObj['displayName'];
        $this->giftIds = $jsonObj['gifts'];
    }

    public function initByGiftMap($giftMap) {
        $this->gifts = [];
        foreach ($this->giftIds as $collectionJson) {
            $collection = new GiftCollection();
            $collection->dataToModel($collectionJson);
            $gift = ArrayUtil::safeGet($giftMap, $collection->giftKindId);
            if ($gift != null) {
                $data['gift'] = $gift;
                $data['giftCollection'] = $collection;
                $this->gifts[] = $data;
            }
        }
    }
}