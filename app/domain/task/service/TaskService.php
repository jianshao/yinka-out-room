<?php


namespace app\domain\task\service;


use app\core\mysql\Sharding;
use app\domain\events\CompleteNewerTaskDomainEvent;
use app\domain\exceptions\FQException;
use app\domain\task\TaskKind;
use app\domain\user\UserRepository;
use app\event\GetTaskRewardEvent;
use app\event\UserTaskWeekSignEvent;

class TaskService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TaskService();
        }
        return self::$instance;
    }

    //任务中心
    public function taskCenter($userId)
    {

        $timestamp = time();
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                // 签到任务
                $checkIn = $user->getTasks()->getCheckInTask($timestamp);

                // 新手任务
                $newerTasks = $user->getTasks()->getNewerTask($timestamp);
                $this->fixCompleteNewerTask($user, $newerTasks, $timestamp);

                // 每日任务
                $dailyTasks = $user->getTasks()->getDailyTask($timestamp);

                // 活跃开包厢任务
                $activeBox = $user->getTasks()->getActiveBoxTask($timestamp);

                return array($checkIn, $newerTasks, $dailyTasks, $activeBox);
            });

        } catch (FQException $e) {
            throw $e;
        }
    }


    private function getTaskFinishNumber($taskArr)
    {
        $number = 0;
        foreach ($taskArr->taskList as $task) {
            if (!$task->hasReward() && $task->isFinished()) {
                $number++;
            }
        }
        return $number;
    }

    /**
     * 任务中心
     * @param $userId
     * @return WeekCheckinTask
     * @throws FQException
     */
    public function weekTaskData($userId)
    {
        $timestamp = time();
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                // 签到任务
                $checkIn = $user->getTasks()->getCheckInTask($timestamp);
                return $checkIn;
            });
        } catch (FQException $e) {
            throw $e;
        }
    }

    public function fixCompleteNewerTask($user, $newerTasks, $timestamp)
    {
        #兼容重构之前有玩家完成新手任务的 领取不了的
        $notFinishTask = [];
        foreach ($newerTasks->taskList as $task) {
            if (!$task->hasReward() && !$task->isFinished()) {
                $notFinishTask[] = $task;
            }
        }

        if (count($notFinishTask) == 1) {
            event(new CompleteNewerTaskDomainEvent($user, $notFinishTask[0]->taskId, $timestamp));
        }
    }

    //获取可领取的任务数量

    /**
     * @param $userId
     * @return int
     * @throws FQException
     */
    public function getRewardTaskCount($userId)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $timestamp = time();
                $num = 0;
                $newerTasks = $user->getTasks()->getNewerTask($timestamp);
                foreach ($newerTasks->taskList as $task) {
                    if (!$task->hasReward() && $task->isFinished()) {
                        $num += 1;
                    }
                }

                $dailyTasks = $user->getTasks()->getDailyTask($timestamp);
                foreach ($dailyTasks->taskList as $task) {
                    if (!$task->hasReward() && $task->isFinished()) {
                        $num += 1;
                    }
                }

                $activeBox = $user->getTasks()->getActiveBoxTask($timestamp);
                foreach ($activeBox->taskList as $task) {
                    $progress = 0;
                    if ($task->taskKind->cycle == TaskKind::$CYCLE_DAY_TYPE) {
                        $progress = intval($user->getAssets()->getActiveDegree($timestamp)->getDayValue());
                    } elseif ($task->taskKind->cycle == TaskKind::$CYCLE_WEEK_TYPE) {
                        $progress = intval($user->getAssets()->getActiveDegree($timestamp)->getWeekValue());
                    }
                    if (!$task->hasReward() && $progress >= $task->taskKind->count) {
                        $num += 1;
                    }
                }

                return $num;
            });

        } catch (FQException $e) {
            throw $e;
        }
    }

    //签到弹窗 如果可以弹返回任务列表
    public function weekSignPop($userId)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $taskList = [];
                $timestamp = time();
                $checkIn = $user->getTasks()->getCheckInTask($timestamp);
                //判断每天弹一次
                if ($checkIn->isPopWeekCheckin($timestamp)) {
                    $taskList = $checkIn->taskList;
                }

                return $taskList;
            });

        } catch (FQException $e) {
            throw $e;
        }
    }

    //签到
    public function weekSign($userId)
    {
        try {
            $rewardItems = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $checkIn = $user->getTasks()->getCheckInTask(time());
                return $checkIn->checkin();
            });
            event(new UserTaskWeekSignEvent($userId,time()));
            return $rewardItems;
        } catch (FQException $e) {
            throw $e;
        }
    }

    //活跃度开宝箱
    public function activeBox($userId, $taskId = null, $num = 0)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $taskId, $num) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $activeBox = $user->getTasks()->getActiveBoxTask(time());
                if ($taskId == null) {
                    $taskId = $activeBox->getTaskSystem()->getTaskIdByNum($num);
                }

                return $activeBox->activeBox($taskId);
            });
        } catch (FQException $e) {
            throw $e;
        }
    }

    //领取任务
    public function getTaskReward($userId, $taskId)
    {
        /*
         * 100-200是每日任务
         * 200-300是新手任务
        * */

        try {
            $rewardItems = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $taskId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $timestamp = time();
                $rewardItems = null;
                if (100 < $taskId && $taskId <= 200) {
                    $daily = $user->getTasks()->getDailyTask($timestamp);
                    $rewardItems = $daily->receiveAward($taskId, $timestamp);
                } elseif (200 < $taskId && $taskId <= 300) {
                    $newer = $user->getTasks()->getNewerTask($timestamp);
                    $rewardItems = $newer->receiveAward($taskId, $timestamp);
                }

                return $rewardItems;
            });
            event(new GetTaskRewardEvent($userId,$rewardItems,time()));
            return $rewardItems;

        } catch (FQException $e) {
            throw $e;
        }


    }
}