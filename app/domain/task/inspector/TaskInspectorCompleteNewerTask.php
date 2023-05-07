<?php


namespace app\domain\task\inspector;

use app\domain\events\CompleteNewerTaskDomainEvent;
use think\facade\Log;


class TaskInspectorCompleteNewerTask extends TaskInspector
{
    public static $TYPE_ID = 'user.complete.newer.task';

    public function processEventImpl($task, $event){
        if($event instanceof CompleteNewerTaskDomainEvent){
            Log::info(sprintf('TaskInspectorCompleteNewerTask::processEventImpl userId=%d progress=%d',
                $event->user->getUserId(), $task->progress + 1));

            $notFinishCount = 0;
            $newerTasks = $event->user->getTasks()->getNewerTask($event->timestamp);
            foreach ($newerTasks->taskList as $task){
                if(!$task->hasReward() and !$task->isFinished()){
                    $notFinishCount += 1;
                }
            }

            if ($notFinishCount == 1){
                return $task->setProgress($task->taskKind->count, $event->timestamp);
            }
        }
        return array(false, 0);
    }

}