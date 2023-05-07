<?php


namespace app\domain\task\system;
use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class WeekCheckinTaskSystem extends TaskSystem
{

    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new WeekCheckinTaskSystem();
            $taskConfig = Config::getInstance()->getWeekCheckInConf();
            self::$instance->loadFromJson(ArrayUtil::safeGet($taskConfig, 'weekcheckin', []));
        }
        return self::$instance;
    }

    public function getCheckInRewards($weekDay) {
        return $this->taskKinds[$weekDay-1];
    }

    public function getTaskIdByWeekDay($weekDay) {
        return $this->taskKinds[$weekDay-1]->taskId;
    }
}