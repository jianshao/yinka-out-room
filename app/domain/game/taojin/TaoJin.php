<?php


namespace app\domain\game\taojin;


use app\utils\ArrayUtil;


class TaoJin
{
    // 游戏ID
    public $gameId = 0;
    // 游戏名称
    public $name = '';
    // 游戏图片
    public $image = '';
    // 地图背景
    public $bgmap = '';
    // 游戏地图
    public $map = '';
    // 地图遮盖
    public $covermap = '';
    // 游戏封面
    public $cover = '';
    // 游戏奖励
    public $rewardList = null;
    // 游戏需要的体力
    public $energy = null;

    public function decodeFromJson($jsonObj) {
        $this->gameId = $jsonObj['gameId'];
        $this->name = $jsonObj['name'];
        $this->image = $jsonObj['image'];
        $this->bgmap = $jsonObj['bgmap'];
        $this->map = $jsonObj['map'];
        $this->covermap = $jsonObj['covermap'];
        $this->cover = $jsonObj['cover'];
        $this->energy = $jsonObj['energy'];

        foreach(ArrayUtil::safeGet($jsonObj, 'diceReward', []) as $rwardConf) {
            $reward = new TaoJinReward();
            $reward->decodeFromJson($rwardConf);
            $this->rewardList[] = $reward;
        }

        return $this;
    }
}