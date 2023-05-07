<?php


namespace app\domain\game\taojin;


use app\domain\asset\AssetItem;


class TaoJinReward
{
    public $id = 0;
    // 权重
    public $weight = 0;
    // 游戏奖励
    public $reward = null;

    public function decodeFromJson($jsonObj) {
        $this->id = $jsonObj['id'];
        $this->weight = $jsonObj['weight'];
        $this->reward = new AssetItem($jsonObj['reward']['assetId'], $jsonObj['reward']['count']);

        return $this;
    }
}