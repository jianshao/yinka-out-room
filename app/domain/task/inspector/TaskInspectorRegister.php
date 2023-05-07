<?php


namespace app\domain\task\inspector;


use app\utils\ClassRegister;

class TaskInspectorRegister extends ClassRegister
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TaskInspectorRegister();
        }
        return self::$instance;
    }

    public function decodeList($jsonObjs) {
        $ret = [];
        foreach ($jsonObjs as $jsonObj){
            $ret[] = $this->decodeFromJson($jsonObj);
        }
        return $ret;
    }
}