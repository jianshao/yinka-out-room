<?php


namespace app\domain\task\inspector;

use app\event\RoomShareEvent;
use think\facade\Log;


class TaskInspectorRoomShare extends TaskInspector
{
    public static $TYPE_ID = 'user.share.room';

    public function processEventImpl($task, $event) {
        if ($event instanceof RoomShareEvent) {
            Log::info(sprintf('TaskInspectorRoomShare::processEventImpl userId=%d progress=%d',
                $event->userId, 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}