<?php


namespace app\domain\task\inspector;

use think\facade\Log;

class TaskInspector
{
    public $displayName = '';

    public function decodeFromJson($jsonObj) {
        $this->displayName = $jsonObj['displayName'];
    }

    public function processEventImpl($task, $event){
        return array(false, 0);
    }

    public function processEvent($task, $event){
        return $this->processEventImpl($task, $event);
    }
}