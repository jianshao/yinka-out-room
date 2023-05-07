<?php


namespace app\domain\task\service;


use app\domain\events\CompleteNewerTaskDomainEvent;
use app\domain\task\dao\NewerModelDao;
use app\domain\task\inspector\TaskInspectorBindMobile;
use app\domain\task\inspector\TaskInspectorCompleteInfo;
use app\domain\task\inspector\TaskInspectorCompleteNewerTask;
use app\domain\task\inspector\TaskInspectorCompleteRealUser;
use app\domain\task\inspector\TaskInspectorCreateRoom;
use app\domain\task\inspector\TaskInspectorFocusFriend;
use app\domain\task\inspector\TaskInspectorOpenMagicBox;
use app\domain\task\inspector\TaskInspectorRegister;
use app\domain\task\inspector\TaskInspectorRoomFocus;
use app\domain\task\inspector\TaskInspectorUserLogin;
use app\domain\task\system\NewerTaskSystem;

class NewerTask extends BaseTask
{
    public function name() {
        return "NewerTask";
    }

    public function __construct($user) {
        parent::__construct($user);
        $this->taskSystem = NewerTaskSystem::getInstance();
        $this->modelDao = NewerModelDao::getInstance();
    }

    public function taskType($taskKind){
        return 'newer';
    }


    public function getTaskReward($task, $timestamp){
        $rewardItems = parent::getTaskReward($task, $timestamp);

        event(new CompleteNewerTaskDomainEvent($this->user, $task->taskId, $timestamp));

        return $rewardItems;
    }

}

TaskInspectorRegister::getInstance()->register(TaskInspectorCompleteInfo::$TYPE_ID, TaskInspectorCompleteInfo::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorRoomFocus::$TYPE_ID, TaskInspectorRoomFocus::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorCompleteNewerTask::$TYPE_ID, TaskInspectorCompleteNewerTask::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorBindMobile::$TYPE_ID, TaskInspectorBindMobile::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorCompleteRealUser::$TYPE_ID, TaskInspectorCompleteRealUser::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorUserLogin::$TYPE_ID, TaskInspectorUserLogin::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorOpenMagicBox::$TYPE_ID, TaskInspectorOpenMagicBox::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorCreateRoom::$TYPE_ID, TaskInspectorCreateRoom::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorFocusFriend::$TYPE_ID, TaskInspectorFocusFriend::class);
