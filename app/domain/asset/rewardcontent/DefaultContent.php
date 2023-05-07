<?php


namespace app\domain\asset\rewardcontent;


use app\domain\asset\AssetItem;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class DefaultContent
{

    public static $TYPE_ID = 'DefaultContent';
    public $content = null;

    public function getContent() {
        return $this->content;
    }

    public function getItem() {
        return $this->content;
    }

    public function decodeFromJson($jsonObj) {
        $name = ArrayUtil::safeGet($jsonObj, 'name');
        $img = ArrayUtil::safeGet($jsonObj, 'img', '');
        $this->content = new AssetItem($jsonObj['assetId'], $jsonObj['count'], $name, $img);

        return $this;
    }

}