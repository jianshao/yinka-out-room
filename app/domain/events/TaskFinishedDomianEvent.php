<?php
namespace app\domain\events;


//完成任务事件
class TaskFinishedDomianEvent extends DomainUserEvent
{
    public $taskId = 0;

    public function __construct($user,$taskId, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->taskId=$taskId;
    }
}