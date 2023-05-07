<?php


namespace app\domain\task\inspector;

use app\event\BreakBoxNewEvent;
use app\event\BreakBoxEvent;
use think\facade\Log;


class TaskInspectorOpenMagicBox extends TaskInspector
{
    public static $TYPE_ID = 'user.open.magicbox';

    public function processEventImpl($task, $event) {
        if($event instanceof BreakBoxEvent ||
            $event instanceof BreakBoxNewEvent) {
            Log::info(sprintf('TaskInspectorOpenMagicBox::processEventImpl userId=%d progress=%d',
                $event->userId, 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}