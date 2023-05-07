<?php

namespace app\domain\mall;

use app\domain\asset\AssetItem;

class GoodsPrice
{
    public $count = 0;
    public $assetItem = null;

    public function decodeFromJson($jsonObj) {
        $this->count = $jsonObj['count'];
        $this->assetItem = new AssetItem();
        $this->assetItem->decodeFromJson($jsonObj['price']);
        return $this;
    }
}


