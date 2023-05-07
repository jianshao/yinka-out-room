<?php


namespace app\domain\task\inspector;

use app\domain\events\CompleteRealUserDomainEvent;
use think\facade\Log;


class TaskInspectorCompleteRealUser extends TaskInspector
{
    public static $TYPE_ID = 'user.complete.realuser';

    public function processEventImpl($task, $event) {
        if ($event instanceof CompleteRealUserDomainEvent) {
            Log::info(sprintf('TaskInspectorCompleteRealUser::processEventImpl userId=%d progress=%d',
                $event->user->getUserId(), 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}