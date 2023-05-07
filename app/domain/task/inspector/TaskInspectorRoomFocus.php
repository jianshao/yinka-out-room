<?php


namespace app\domain\task\inspector;

use app\event\RoomAttentionEvent;
use think\facade\Log;


class TaskInspectorRoomFocus extends TaskInspector
{
    public static $TYPE_ID = 'user.focus.room';

    public function processEventImpl($task, $event){
        if($event instanceof RoomAttentionEvent){
            Log::info(sprintf('TaskInspectorRoomFocus::processEventImpl userId=%d progress=%d',
                $event->userId, 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }
}