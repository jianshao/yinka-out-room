<?php


namespace app\domain\task\service;


use app\domain\bi\BIReport;
use app\domain\events\TaskFinishedDomianEvent;
use app\domain\events\TaskRewardDomianEvent;
use app\domain\exceptions\FQException;
use app\domain\task\Task;
use app\utils\ArrayUtil;
use think\facade\Log;

class BaseTask
{
    protected $_isLoaded = false;
    protected $user;
    protected $userId = 0;
    protected $modelDao;
    protected $taskSystem;
    public $taskList = [];

    public function __construct($user)
    {
        $this->user = $user;
        $this->userId = $user->getUserId();

    }

    public function name()
    {
        return "BaseTask";
    }

    public function taskType($taskKind)
    {
        return 'none';
    }

    public function getTaskSystem()
    {
        return $this->taskSystem;
    }

    /**
     * 是否加载了
     */
    public function isLoaded()
    {
        return $this->_isLoaded;
    }

    /**
     * 加载用户背包
     */
    public function load($timestamp)
    {
        if (!$this->isLoaded()) {
            $this->doLoad($timestamp);
            $this->_isLoaded = true;
            Log::info(sprintf('TaskService.%s userId=%d', $this->name(), $this->userId));
        }
    }

    //load是不改变数据库任务 只改变内存
    private function doLoad($timestamp)
    {
        $userId = $this->userId;
        $userTasks = [];
        $userTaskMap = [];

        $taskKindMap = $this->taskSystem->getTaskKindMap();
        $taskList = $this->modelDao->loadAllTasks($userId);
        if (!empty($taskList)) {
            # 检查任务 1、是否在配置中存在 2、是否到了刷新时间 有的任务一周刷新 有的任务一天刷新
            foreach ($taskList as $taskData) {
                $kindId = $taskData["taskId"];
                $taskKind = $this->taskSystem->findTaskKind($kindId);
                if ($taskKind == null) {
                    $this->modelDao->removeTask($userId, $kindId);
                } else {
                    $task = new Task($taskKind, $timestamp);
                    $task->decodeFromTaskData($taskData);
                    $userTaskMap[$kindId] = $task;
                    if ($task->isUpdate($timestamp)) {
                        $userTasks[] = $this->updateTask($taskKind, $timestamp);;
                    } else {
                        $userTasks[] = $task;
                    }
                }
            }
        }

        // 检查load的任务是否全，配置可能新增里了任务
        if (count($userTaskMap) != count($taskKindMap)) {
            foreach ($taskKindMap as $kindId => $taskKind) {
                if (ArrayUtil::safeGet($userTaskMap, $kindId, null) == null) {
                    $task = $this->addTask($taskKind, $timestamp);
                    $userTasks[] = $task;
                }
            }
        }

        $this->taskList = $userTasks;
        return $userTasks;
    }

    private function addTask($taskKind, $timestamp)
    {
        $task = new Task($taskKind, $timestamp);
        $this->modelDao->addTask($this->userId, $task);
        return $task;
    }

    private function updateTask($taskKind, $timestamp)
    {
        $task = new Task($taskKind, $timestamp);
        $this->modelDao->updateTask($this->userId, $task);
        return $task;
    }

    // 通过taskId load当前任务
    public function getUserTask($taskId)
    {
        foreach ($this->taskList as $task) {
            if ($task->taskId == $taskId) {
                return $task;
            }
        }

        throw new FQException('没有该任务', 500);
    }

    public function onTaskUpdated($task)
    {
        $this->modelDao->updateTask($this->userId, $task);
    }

    public function onTaskFinished($task, $timestamp)
    {
        assert($task instanceof Task);

//        创建事件，生成小红点
        event(new TaskFinishedDomianEvent($this->user, $task->taskId, $timestamp));

        if ($task->taskKind->reward != null && $task->taskKind->autoSendReward)
            $this->getTaskReward($task, $timestamp);
    }

    public function getTaskReward($task, $timestamp)
    {
        assert($task instanceof Task);
        if ($task->hasReward()) {
            throw new FQException('您已经完成过此任务', 500);
        }

        if (!$task->isFinished()) {
            throw new FQException('任务还未完成', 500);
        }
        Log::info(sprintf('TaskService.%s getTaskReward userId=%d taskId=%d',
            $this->name(), $this->userId, $task->taskId));

        $task->gotReward = 1;
        $task->finishTime = $timestamp;
        $task->updateTime = $timestamp;
        $this->modelDao->updateTask($this->userId, $task);

        return $this->sendTaskReward($task);
    }

    # 领取任务奖励
    public function sendTaskReward($task)
    {
        $timestamp = time();
        $userAssets = $this->user->getAssets();

        $rewardItems = [];
        $rewards = $task->taskKind->reward;
        $biEvent = BIReport::getInstance()->makeTaskBIEvent($this->taskType($task->taskKind), $task->taskId);
        foreach ($rewards as $reward) {
            $rewardItem = $reward->getItem();
            $userAssets->add($rewardItem->assetId, $rewardItem->count, $timestamp, $biEvent);

            $rewardItems[] = $rewardItem;
        }

        Log::info(sprintf('TaskService.%s sendTaskReward ok userId=%d count=%d',
            $this->name(), $this->userId, count($rewardItems)));

        event(new TaskRewardDomianEvent($this->user, $task->taskId, $timestamp));
        return $rewardItems;
    }

    public function handleDomainEvent($event)
    {
        $this->handleEventImpl($event);
    }

    private function handleEventImpl($event)
    {
        foreach ($this->taskList as $task) {
            if ($task->gotReward){
                continue;
            }

            list($changed, $finishCount) = $task->processEvent($event);
            Log::debug(sprintf('TaskService.%s handleEventImpl userId=%d taskId=%d changed=%d, finishCount=%d',
                $this->name(), $this->userId, $task->taskId, $changed, $finishCount));
            if ($changed)
                $this->onTaskUpdated($task);
            if ($finishCount > 0)
                $this->onTaskFinished($task, $event->timestamp);
        }
    }

    public function receiveAward($taskId, $timestamp)
    {
        $task = $this->getUserTask($taskId);

        if ($task->isUpdate($timestamp)) {
            throw new FQException('任务已过期', 500);
        }

        return $this->getTaskReward($task, $timestamp);
    }

}