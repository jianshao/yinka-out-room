<?php


namespace app\domain\task\inspector;

use app\common\GetuiCommon;
use app\event\ReleaseDynamicEvent;
use think\facade\Log;


class TaskInspectorReleaseDynamic extends TaskInspector
{
    public static $TYPE_ID = 'user.release.dynamic';

    public function processEventImpl($task, $event){
        if($event instanceof ReleaseDynamicEvent){
            Log::info(sprintf('TaskInspectorReleaseDynamic::processEventImpl userId=%d progress=%d',
                $event->userId, 1));
            GetuiCommon::getInstance()->pushMessageToSingle($event->userId, 2);
            return $task->setProgress(1, $event->timestamp);
        }
        return array(false, 0);
    }

}