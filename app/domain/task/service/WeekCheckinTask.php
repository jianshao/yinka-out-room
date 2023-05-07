<?php


namespace app\domain\task\service;

use app\domain\exceptions\FQException;
use app\domain\task\dao\WeekCheckinModelDao;
use app\domain\task\system\WeekCheckinTaskSystem;
use app\utils\TimeUtil;
use think\facade\Log;


class WeekCheckinTask extends BaseTask
{

    public function name() {
        return "WeekCheckinTask";
    }

    public function __construct($user) {
        parent::__construct($user);
        $this->modelDao = WeekCheckinModelDao::getInstance();
        $this->taskSystem = WeekCheckinTaskSystem::getInstance();
    }

    public function taskType($taskKind){
        return 'weekCheckin';
    }

    public function isPopWeekCheckin($timestamp){
//        if (config("config.appDev")=='dev'){
//            return true;
//        }

        $changeTime = $this->modelDao->getPopCheckinTime($this->userId);
        $need = $changeTime == 0 ? true:!TimeUtil::isSameDay($timestamp, $changeTime);
        if($need){
            Log::info(sprintf('WeekCheckinService isPopWeekCheckin $userId=%d $t1=%d $t2=%d', $this->userId,
                strftime('%Y-%m-%d', $timestamp), strftime('%Y-%m-%d', $changeTime)));
            $this->modelDao->updatePopChangeTime($this->userId, $timestamp);

            $weekday = date('w', $timestamp) == 0 ? 7 : date('w', $timestamp);
            $taskId = $this->taskSystem->getTaskIdByWeekDay((int)$weekday);
            $curTask = $this->getUserTask($taskId);
            if($curTask->hasReward()){
                return false;
            }

            return true;
        }

        return false;
    }

    public function checkin() {
        $weekday = date('w') == 0 ? 7 : date('w');
        $taskId = $this->taskSystem->getTaskIdByWeekDay((int)$weekday);
        Log::info(sprintf('WeekCheckinService checkin $userId=%d $weekday=%d $taskId=%d', $this->userId, $weekday, $taskId));

        $curTask = $this->getUserTask($taskId);
        if($curTask->hasReward()){
            throw new FQException('您今天已经完成签到', 500);
        }

        $curTask->finishCount = 1;
        $this->onTaskUpdated($curTask);
        $rewardItems = $this->getTaskReward($curTask, time());
        Log::info(sprintf('WeekCheckinService checkin ok $userId=%d $weekday=%d', $this->userId, $weekday));
        return $rewardItems;
    }

    public function getTaskIdByWeekDay($weekDay) {
        return $this->taskSystem->getTaskIdByWeekDay($weekDay);
    }

}