<?php


namespace app\domain\task\system;
use app\domain\Config;
use app\utils\ArrayUtil;


class ActiveBoxTaskSystem extends TaskSystem
{

    protected static $instance;
    public $activeInfo = '';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ActiveBoxTaskSystem();
            $taskConfig= Config::getInstance()->getActiveBoxConfig();
            self::$instance->loadFromJson(ArrayUtil::safeGet($taskConfig, 'activebox', []));
            self::$instance->activeInfo = ArrayUtil::safeGet($taskConfig, 'activeinfo', []);
        }
        return self::$instance;
    }

    //通过任务条件获取任务id
    public function getTaskIdByNum($num){
        foreach ($this->taskKinds as $taskKind){
            if($taskKind->count == $num){
                return $taskKind->taskId;
            }
        }

        return null;
    }
}