<?php


namespace app\domain\task\inspector;

use app\domain\dao\LoginDetailNewModelDao;
use app\domain\user\event\UserLoginDomainEvent;
use think\facade\Log;


class TaskInspectorUserLogin extends TaskInspector
{
    public static $TYPE_ID = 'user.login';

    public function processEventImpl($task, $event) {
        if ($event instanceof UserLoginDomainEvent) {
            $count = LoginDetailNewModelDao::getInstance()->getOneDayLoginNumber($event->user->getUserId(), $event->timestamp);
            Log::info(sprintf('TaskInspectorUserLogin::processEventImpl userId=%d taskId=%d progress=%d $count=%d',
                $event->user->getUserId(), $task->taskId, $task->progress, $count));
            if ($count > 1){
                return array(false, 0);
            }

            return $task->setProgress($task->progress + 1, $event->timestamp);
        }
        return array(false, 0);
    }

}