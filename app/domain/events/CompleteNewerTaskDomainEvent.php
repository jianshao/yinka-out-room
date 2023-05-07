<?php

namespace app\domain\events;

use think\Log;

class CompleteNewerTaskDomainEvent extends DomainUserEvent
{
    public $taskId = null;
    public function __construct($user, $taskId, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->taskId = $taskId;
    }
}