<?php


namespace app\domain\task\service;


use app\domain\task\inspector\TaskInspectorPrivateChat;
use app\domain\task\inspector\TaskInspectorRechare;
use app\domain\task\inspector\TaskInspectorReleaseDynamic;
use app\domain\task\inspector\TaskInspectorRoomFocus;
use app\domain\task\inspector\TaskInspectorRoomShare;
use app\domain\task\inspector\TaskInspectorRoomStayFiveMinutes;
use app\domain\task\inspector\TaskInspectorSendBean;
use app\domain\task\inspector\TaskInspectorUserLogin;
use app\domain\task\system\DailyTaskSystem;
use app\domain\task\dao\DailyModelDao;
use app\domain\task\inspector\TaskInspectorRegister;


class DailyTask extends BaseTask
{
    public function name() {
        return "DailyTask";
    }

    public function __construct($user) {
        parent::__construct($user);
        $this->modelDao = DailyModelDao::getInstance();
        $this->taskSystem = DailyTaskSystem::getInstance();
    }

    public function taskType($taskKind){
        return 'daily';
    }
}

TaskInspectorRegister::getInstance()->register(TaskInspectorUserLogin::$TYPE_ID, TaskInspectorUserLogin::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorPrivateChat::$TYPE_ID, TaskInspectorPrivateChat::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorRechare::$TYPE_ID, TaskInspectorRechare::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorReleaseDynamic::$TYPE_ID, TaskInspectorReleaseDynamic::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorRoomFocus::$TYPE_ID, TaskInspectorRoomFocus::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorRoomShare::$TYPE_ID, TaskInspectorRoomShare::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorRoomStayFiveMinutes::$TYPE_ID, TaskInspectorRoomStayFiveMinutes::class);
TaskInspectorRegister::getInstance()->register(TaskInspectorSendBean::$TYPE_ID, TaskInspectorSendBean::class);