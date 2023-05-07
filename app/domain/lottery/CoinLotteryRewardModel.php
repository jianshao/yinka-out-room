<?php


namespace app\domain\lottery;



class CoinLotteryRewardModel
{
    public $userId = 0;
    // 1头像，气泡框 2金币 3礼
    public $rewardType = 0;
    // 金币id为'' 其他的实际id
    public $rewardId = 0;
    // 奖励的数量
    public $num = 0;
    public $createTime = 0;

    public function __construct($userId=0, $rewardId='', $rewardType=0, $num=0, $createTime=0) {
        $this->userId = $userId;
        $this->rewardType = $rewardType;
        $this->rewardId = $rewardId;
        $this->num = $num;
        $this->createTime = $createTime;
    }

}