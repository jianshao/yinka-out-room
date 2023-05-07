<?php

namespace app\event;


//我的-任务中心
class TaskCenterEvent extends AppEvent
{
    public $userId = 0;  //用户id
    public $finishNumber = 0;  //可领取的奖励数量

    public function __construct($userId, $finishNumber, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->finishNumber = $finishNumber;
    }
}