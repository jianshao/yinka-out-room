<?php


namespace app\domain\task;


use app\domain\task\service\ActiveBoxTask;
use app\domain\task\service\DailyTask;
use app\domain\task\service\NewerTask;
use app\domain\task\service\WeekCheckinTask;
use think\facade\Log;

class UserTasks
{
    // 用户ID
    private $user = 0;
    // 新手任务
    private $newerTask = null;
    // 每日任务
    private $dailyTask = null;
    // 签到任务
    private $checkInTask = null;
    // 活跃宝箱任务
    private $activeBoxTask = null;

    public function __construct($user) {
        $this->user = $user;
        $this->newerTask = null;
        $this->dailyTask = null;
        $this->checkInTask = null;
        $this->activeBoxTask = null;
        Log::info(sprintf('UserTasks init %d', $user->getUserId()));
    }

    public function initTasks($timestamp) {
        $this->getNewerTask($timestamp);
        $this->getDailyTask($timestamp);
        $this->getCheckInTask($timestamp);
        $this->getActiveBoxTask($timestamp);
    }

    public function getNewerTask($timestamp) {
        if ($this->newerTask == null) {
            $newerTask = new NewerTask($this->user);
            $newerTask->load($timestamp);
            $this->newerTask = $newerTask;
        }
        return $this->newerTask;
    }

    public function getDailyTask($timestamp) {
        if ($this->dailyTask == null) {
            $dailyTask = new DailyTask($this->user);
            $dailyTask->load($timestamp);
            $this->dailyTask = $dailyTask;
        }
        return $this->dailyTask;
    }

    public function getCheckInTask($timestamp) {
        if ($this->checkInTask == null) {
            $checkInTask = new WeekCheckinTask($this->user);
            $checkInTask->load($timestamp);
            $this->checkInTask = $checkInTask;
        }
        return $this->checkInTask;
    }

    public function getActiveBoxTask($timestamp) {
        if ($this->activeBoxTask == null) {
            $activeBoxTask = new ActiveBoxTask($this->user);
            $activeBoxTask->load($timestamp);
            $this->activeBoxTask = $activeBoxTask;
        }
        return $this->activeBoxTask;
    }
}