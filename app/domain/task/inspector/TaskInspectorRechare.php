<?php


namespace app\domain\task\inspector;

use app\event\ChargeEvent;
use think\facade\Log;


class TaskInspectorRechare extends TaskInspector
{
    public static $TYPE_ID = 'user.recharge';

    public function processEventImpl($task, $event) {
        if ($event instanceof ChargeEvent) {
            Log::DEBUG(sprintf('TaskInspectorRechare::processEventImpl userId=%d progress=%d',
                $event->userId, 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}