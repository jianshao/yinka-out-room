<?php


namespace app\domain\task\inspector;

use app\domain\events\BindMobileDomainEvent;
use app\domain\user\event\UserLoginDomainEvent;
use think\facade\Log;


class TaskInspectorBindMobile extends TaskInspector
{
    public static $TYPE_ID = 'user.bind.mobile';

    public function processEventImpl($task, $event){
        if ($event instanceof BindMobileDomainEvent
            || ($event instanceof UserLoginDomainEvent
                && (!empty($event->user->getUserModel()->mobile || !empty($event->user->getUserModel()->username))))) {
            Log::info(sprintf('TaskInspectorBindMobile::processEventImpl userId=%d progress=%d',
                $event->user->getUserId(), 1));
            
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}