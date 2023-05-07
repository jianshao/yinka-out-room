<?php


namespace app\domain\game\gashapon;



class GashaponRewardModel
{
    public $userId = 0;
    public $rewardId = 0;
    // 奖励的数量
    public $rewardCount = 0;
    public $createTime = 0;

    public function __construct($userId=0, $rewardId='', $num=0, $createTime=0) {
        $this->userId = $userId;
        $this->rewardId = $rewardId;
        $this->rewardCount = $num;
        $this->createTime = $createTime;
    }

}