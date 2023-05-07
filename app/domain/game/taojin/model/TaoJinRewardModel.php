<?php


namespace app\domain\game\taojin\model;



class TaoJinRewardModel
{
    public $id = 0;
    public $userId = 0;
    // 1化石 2金 3银4铁 5豆
    public $rewardType = 0;
    // 奖励的数量
    public $num = 0;
    // 哪个地图
    public $gameId= 0;
    public $createTime = 0;

    public function __construct($userId=0, $gameId=0, $rewardType=0, $num=0, $createTime=0) {
        $this->userId = $userId;
        $this->rewardType = $rewardType;
        $this->gameId = $gameId;
        $this->num = $num;
        $this->createTime = $createTime;
    }

}