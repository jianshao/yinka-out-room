<?php


namespace app\domain\task\inspector;

use app\event\RoomStaySecondEvent;
use think\facade\Log;


class TaskInspectorRoomStayFiveMinutes extends TaskInspector
{
    public static $TYPE_ID = 'user.stay.room.5m';

    public function processEventImpl($task, $event) {
        if ($event instanceof RoomStaySecondEvent) {
            Log::info(sprintf('TaskInspectorRoomStayFiveMinutes::processEventImpl userId=%d progress=%d second=%d',
                $event->userId, $task->progress + $event->second, $event->second));
            return $task->setProgress($task->progress + $event->second, $event->timestamp);
        }
        return array(false, 0);
    }

}