<?php


namespace app\domain\game\poolbase\condition;
use app\utils\ClassRegister;

class PoolConditionRegister extends ClassRegister
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PoolConditionRegister();
            self::$instance->register(PoolConditionConsume::$TYPE_ID, PoolConditionConsume::class);
            self::$instance->register(PoolConditionBaolv::$TYPE_ID, PoolConditionBaolv::class);
            self::$instance->register(PoolConditionAnd::$TYPE_ID, PoolConditionAnd::class);
            self::$instance->register(PoolConditionOr::$TYPE_ID, PoolConditionOr::class);
        }
        return self::$instance;
    }

}