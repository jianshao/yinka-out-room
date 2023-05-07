<?php


namespace app\domain\task\dao;

use app\core\mysql\ModelDao;
use app\domain\task\Task;
use think\Model;
use app\common\RedisCommon;

class TaskModelDao extends ModelDao
{
    protected $table = '';
    protected $pk = 'uid';
    protected $serviceName = 'userMaster';

    public function loadAllTasks($userId) {
        $taskList = $this->getModel($userId)->where(['uid' => $userId])->select()->toArray();
        return !empty($taskList)? $taskList: [];
    }

    public function loadTask($userId, $taskId) {
        $taskData = $this->getModel($userId)->where(['uid' => $userId, 'taskId'=>$taskId])->find();
        return !empty($taskData)? $taskData: [];
    }

    public function addTask($userId, $task) {
        assert($task instanceof Task);
        $data = $task->encodeToTaskData();
        $data['uid'] = $userId;
        $this->getModel($userId)->insert($data);
    }

    public function updateTask($userId, $task){
        assert($task instanceof Task);
        $data = $task->encodeToTaskData();
        $data['uid'] = $userId;
        $this->getModel($userId)->where(['uid' => $userId, 'taskId' => $task->taskId])->update($data);
    }

    public function removeTask($userId, $taskId) {
        $this->getModel($userId)->where(['uid' => $userId, 'taskId' => $taskId])->delete();
    }

    public function removeAllTask($userId) {
        $this->getModel($userId)->where(['uid' => $userId])->delete();
    }
}
