<?php


namespace app\domain\asset\rewardcontent;


use app\domain\asset\AssetItem;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

class SingleRandomContent
{
    public static $TYPE_ID = 'SingleRandomContent';
    public $content = [];
    public $randValues = null;

    public function getContent() {
        $count = random_int($this->randValues[0], $this->randValues[1]);
        $this->content->count = $count;
        return $this->content;
    }

    public function getItem() {
        $count = random_int($this->randValues[0], $this->randValues[1]);
        $this->content->count = $count;
        return $this->content;
    }

    public function decodeFromJson($jsonObj) {
        if (count($jsonObj['randValues']) != 2 || $jsonObj['randValues'][0] >= $jsonObj['randValues'][1]){
            throw new FQException('randValues conf error', -1);
        }

        $name = ArrayUtil::safeGet($jsonObj, 'name');
        $img = ArrayUtil::safeGet($jsonObj, 'img', '');
        $this->randValues = $jsonObj['randValues'];
        $this->content = new AssetItem($jsonObj['assetId'], 0, $name, $img);

        return $this;
    }

}