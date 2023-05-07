<?php


namespace app\domain\task\inspector;

use app\event\PrivateChatEvent;
use think\facade\Log;


class TaskInspectorPrivateChat extends TaskInspector
{
    public static $TYPE_ID = 'user.private.chat';

    public function processEventImpl($task, $event) {
        if ($event instanceof PrivateChatEvent) {
            Log::DEBUG(sprintf('TaskInspectorPrivateChat::processEventImpl userId=%d progress=%d',
                $event->userId, 1));
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}