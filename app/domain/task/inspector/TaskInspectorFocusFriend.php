<?php


namespace app\domain\task\inspector;

use app\domain\events\FocusFriendDomainEvent;
use think\facade\Log;


class TaskInspectorFocusFriend extends TaskInspector
{
    public static $TYPE_ID = 'user.focus.friend';

    public function processEventImpl($task, $event) {
        if ($event instanceof FocusFriendDomainEvent) {
            Log::info(sprintf('TaskInspectorFocusFriend::processEventImpl userId=%d progress=%d',
                $event->user->getUserId(), 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}