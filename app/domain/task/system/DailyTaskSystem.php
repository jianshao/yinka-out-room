<?php


namespace app\domain\task\system;
use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;


class DailyTaskSystem extends TaskSystem
{

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DailyTaskSystem();
            $taskConfig = Config::getInstance()->getDailyConfig();
            self::$instance->loadFromJson(ArrayUtil::safeGet($taskConfig, 'daily', []));
        }
        return self::$instance;
    }
}