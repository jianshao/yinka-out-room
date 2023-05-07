<?php

namespace app\domain\mall;

use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 货架
 */
class Shelves
{
    public $type = '';
    public $displayName = '';
    public $goodsIdList = [];
    public $goodsList = [];

    public function decodeFromJson($jsonObj) {
        $this->type = ArrayUtil::safeGet($jsonObj, 'type', '');
        $this->displayName = $jsonObj['displayName'];
        $this->goodsIdList = [];
        foreach (ArrayUtil::safeGet($jsonObj, 'goodsIds', []) as $goodsId) {
            $this->goodsIdList[] = $goodsId;
        }
        return $this;
    }

    public function initGoodsList($goodsMap) {
        foreach ($this->goodsIdList as $goodsId) {
            $goods = ArrayUtil::safeGet($goodsMap, $goodsId);
            if ($goods != null) {
                $this->goodsList[] = $goods;
            } else {
                Log::warning(sprintf('Unknown goodsId: %d', $goodsId));
            }
        }
    }
}