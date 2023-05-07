<?php


namespace app\domain\task\system;
use app\domain\Config;
use app\utils\ArrayUtil;

class NewerTaskSystem extends TaskSystem
{

    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new NewerTaskSystem();
            $taskConfig= Config::getInstance()->getNewerConfig();
            self::$instance->loadFromJson(ArrayUtil::safeGet($taskConfig, 'newer', []));
        }
        return self::$instance;
    }

}