<?php
namespace app\domain\events;

//领取任务奖励事件
class TaskRewardDomianEvent extends DomainUserEvent
{
    public $taskId = 0;

    public function __construct($user,$taskId, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->taskId=$taskId;
    }
}