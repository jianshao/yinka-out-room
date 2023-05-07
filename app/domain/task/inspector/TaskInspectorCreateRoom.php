<?php


namespace app\domain\task\inspector;

use app\event\RoomCreateEvent;
use think\facade\Log;


class TaskInspectorCreateRoom extends TaskInspector
{
    public static $TYPE_ID = 'user.create.room';

    public function processEventImpl($task, $event) {
        if($event instanceof RoomCreateEvent){
            Log::info(sprintf('TaskInspectorCreateRoom::processEventImpl userId=%d progress=%d',
                $event->userId, 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}