<?php

namespace app\event;


//领取完任务奖励
class GetTaskRewardEvent extends AppEvent
{
    public $userId = 0;
    public $rewardItems = null;

    public function __construct($userId, $rewardItems, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->rewardItems = $rewardItems;
    }
}