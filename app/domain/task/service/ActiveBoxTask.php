<?php


namespace app\domain\task\service;


use app\domain\exceptions\FQException;
use app\domain\task\dao\ActiveBoxModelDao;
use app\domain\task\system\ActiveBoxTaskSystem;
use app\domain\task\TaskKind;
use think\facade\Log;

class ActiveBoxTask extends BaseTask
{
    public function name() {
        return "ActiveBoxTask";
    }

    public function __construct($user) {
        parent::__construct($user);
        $this->taskSystem = ActiveBoxTaskSystem::getInstance();
        $this->modelDao = ActiveBoxModelDao::getInstance();
    }

    public function taskType($taskKind){
        if($taskKind->cycle == TaskKind::$CYCLE_WEEK_TYPE){
            return 'activeBoxWeek';
        }
        return 'activeBoxDay';
    }

    public function getActiveInfo(){
        return $this->taskSystem->activeInfo;
    }

    public function activeBox($taskId) {
        $timestamp = time();

        Log::info(sprintf('ActiveBoxService activeBox $userId=%d, $taskId=%d', $this->userId, $taskId));

        $task = $this->getUserTask($taskId);
        if($task->isUpdate($timestamp)){
            throw new FQException('任务已过期', 500);
        }

        if($task->hasReward()){
            throw new FQException('您今天已经完成签到', 500);
        }

        # 签到发奖励
        if(!$this->isEnoughActiveDegree($task, $timestamp)){
            throw new FQException('当前活跃值不足', 500);
        }

        $task->finishCount = 1;
        $this->onTaskUpdated($task);
        $rewardItems = $this->getTaskReward($task, time());
        Log::info(sprintf('ActiveBoxService activeBox ok $userId=%d $taskId=%d', $this->userId, $taskId));
        return $rewardItems;
    }

    public function isEnoughActiveDegree($task, $timestamp){
        $activeDegree = 0;
        if($task->taskKind->cycle == TaskKind::$CYCLE_DAY_TYPE){
            $activeDegree = intval($this->user->getAssets()->getActiveDegree($timestamp)->getDayValue());
        } elseif ($task->taskKind->cycle == TaskKind::$CYCLE_WEEK_TYPE){
            $activeDegree = intval($this->user->getAssets()->getActiveDegree($timestamp)->getWeekValue());
        }

        return $activeDegree >= $task->taskKind->count;
    }
}