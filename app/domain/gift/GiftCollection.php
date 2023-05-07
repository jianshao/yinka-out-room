<?php

namespace app\domain\gift;

use app\utils\ArrayUtil;

class GiftCollection
{
    public $giftKindId = '';
    public $collectionDesc = '';
    public $collectionImage = '';

    public function dataToModel($collection) {
       $this->giftKindId = ArrayUtil::safeGet($collection,'kindId');
       $this->collectionDesc = ArrayUtil::safeGet($collection,'collectionDesc');
       $this->collectionImage = ArrayUtil::safeGet($collection,'collectionImg');
    }
}