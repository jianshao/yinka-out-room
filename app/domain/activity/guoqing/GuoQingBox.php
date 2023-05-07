<?php


namespace app\domain\activity\guoqing;


use app\domain\asset\AssetItem;

class GuoQingBox
{
    // boxID
    public $boxId = 0;
    // 名称
    public $name = null;
    // 能量
    public $energy = null;
    // 奖励
    public $rewards = [];

    public function fromJson($jsonObj) {
        $this->boxId = $jsonObj['id'];
        $this->name = $jsonObj['name'];
        $this->energy = $jsonObj['energy'];
        $this->name = $jsonObj['name'];
        foreach ($jsonObj['rewards'] as $reward){
            $this->rewards[] = new AssetItem($reward['assetId'], $reward['count']);
        }

        return $this;
    }
}