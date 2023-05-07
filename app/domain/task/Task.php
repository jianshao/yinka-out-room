<?php


namespace app\domain\task;


use app\utils\TimeUtil;
use think\facade\Log;

//单个任务实例
class Task
{
    # 哪种任务
    public $taskKind = null;
    # 任务id
    public $taskId = 0;
    # 本此任务进度
    public $progress = 0;
    # 总共完成该任务多少次
    public $finishCount = 0;
    # 完成任务时间
    public $finishTime = 0;
    # 是否领取了奖励
    public $gotReward = 0;
    # 最后更新时间
    public $updateTime = 0;

    public function __construct($taskKind, $timestamp) {
        $this->taskKind = $taskKind;
        $this->taskId = $this->taskKind->taskId;
        $this->updateTime = $timestamp;
    }

    public function isUpdate($timestamp) {
        if ($this->taskKind->cycle == TaskKind::$CYCLE_DAY_TYPE
            && !TimeUtil::isSameDay($this->updateTime, $timestamp)) {
            return true;
        } elseif ($this->taskKind->cycle == TaskKind::$CYCLE_WEEK_TYPE
            && !TimeUtil::isSameWeek($this->updateTime, $timestamp)) {
            return true;
        }

        return false;
    }

    public function isFinished() {
        return $this->finishCount > 0;
    }

    public function hasReward() {
        return $this->gotReward > 0;
    }

    public function setProgress($progress, $timestamp) {
        if ($progress == $this->progress){
            return array(false, 0);
        }

        if ($this->taskKind->totalCount <= $this->finishCount){
            return array(false, 0);
        }

        $this->updateTime = $timestamp;
        $this->progress = $progress;

        if ($this->progress < $this->taskKind->count){
            return array(true, 0);
        }

        $this->finishCount += 1;
        $this->finishTime = $timestamp;
        return array(true, 1);
    }

    public function processEvent($event) {
        foreach ($this->taskKind->inspectors as $inspector) {
            list($processFinish, $count) = $inspector->processEvent($this, $event);
            if($processFinish) {
                return array(true, $count);
            }
        }
        return array(false, 0);
    }

    public function encodeToTaskData(){
        return [
            "taskId" => $this->taskKind->taskId,
            "progress" => $this->progress,
            "finishCount" => $this->finishCount,
            "finishTime" => $this->finishTime,
            "gotReward" => $this->gotReward,
            "updateTime" => $this->updateTime
        ];
    }

    public function decodeFromTaskData($taskData){
        $this->progress = $taskData['progress'];
        $this->finishCount = $taskData['finishCount'];
        $this->finishTime = $taskData['finishTime'];
        $this->gotReward = $taskData['gotReward'];
        $this->updateTime = $taskData['updateTime'];
        return $this;
    }
}