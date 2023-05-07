<?php

namespace app\domain\asset;

use app\utils\ArrayUtil;

class AssetItem
{
    public $assetId = '';
    public $count = 0;
    public $name = '';
    public $img = '';

    public function __construct($assetId='', $count=0, $name='', $img='') {
        $this->assetId = $assetId;
        $this->count = $count;
        $this->name = $name;
        $this->img = $img;
    }

    public function decodeFromJson($jsonObj) {
        $this->assetId = $jsonObj['assetId'];
        $this->count = $jsonObj['count'];
        $this->name = ArrayUtil::safeGet($jsonObj, 'name', '');
        $this->img = ArrayUtil::safeGet($jsonObj, 'img', '');
        return $this;
    }

    public static function decodeList($jsonObjs) {
        $ret = [];
        foreach ($jsonObjs as $jsonObj) {
            $item = new AssetItem();
            $item->decodeFromJson($jsonObj);
            $ret[] = $item;
        }
        return $ret;
    }

    public static function toJson($assetItem) {
        return [
            'assetId' => $assetItem->assetId,
            'count' => $assetItem->count
        ];
    }

    public static function toJsonList($assetItems) {
        $ret = [];
        foreach ($assetItems as $assetItem) {
            $ret[] = self::toJson($assetItem);
        }
        return $ret;
    }

    public static function hasAssets($assetItems, $assetIds) {
        foreach ($assetItems as $assetItem) {
            if (in_array($assetItem->assetId, $assetIds)) {
                return true;
            }
        }
        return false;
    }

    public static function calcAssetCount($assetItems, $assetId) {
        $count = 0;
        foreach ($assetItems as $assetItem) {
            if ($assetItem->assetId == $assetId) {
                $count += $assetItem->count;
            }
        }
        return $count;
    }
}


