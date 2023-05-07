<?php
namespace app\domain\task\system;

use app\domain\task\TaskKind;
use app\utils\ArrayUtil;
use think\facade\Log;

class TaskSystem
{
    protected $taskKindMap = [];
    protected $taskKinds = [];

    public function findTaskKind($taskId) {
        return ArrayUtil::safeGet($this->taskKindMap, $taskId);
    }

    public function getTaskKinds() {
        return $this->taskKinds;
    }

    public function getTaskKindMap() {
        return $this->taskKindMap;
    }

    public function getRewardsByTaskId($taskId) {
        $taskKind =  ArrayUtil::safeGet($this->taskKindMap, $taskId);
        if ($taskKind != null){
            return $taskKind->reward;
        }

        return [];
    }

    protected function loadFromJson($taskConfig) {
        $taskKindMap = [];
        $taskKinds = [];
        foreach($taskConfig as $conf) {
            $taskKind = new TaskKind();
            $taskKind->decodeFromJson($conf);
            if (ArrayUtil::safeGet($taskKindMap, $taskKind->taskId) != null) {
                Log::warning(sprintf('TaskSystemLoadErrro taskId=%s err=%s',
                    $taskKind->taskId, 'DuplicateTaskId'));
            } else {
                $taskKinds[] = $taskKind;
                $taskKindMap[$taskKind->taskId] = $taskKind;
            }
        }
        $this->taskKinds = $taskKinds;
        $this->taskKindMap = $taskKindMap;
    }

}

