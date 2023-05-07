<?php

namespace app\api\script\queue;


use app\domain\exceptions\FQException;

/**
 * @info  sub消息订阅者
 * Class UserBucket
 */
class ClassRegister
{

    private static $instance;
    # 类型map
    private $typeMap = [];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ClassRegister();
        }
        return self::$instance;
    }

    public function register($typeName, $factory)
    {
        $this->typeMap[$typeName] = $factory;
    }

    public function handle($handle, $method, $data)
    {
        if ($handle != null) {
            $factory = $this->typeMap[$handle];
            $ret = new $factory();
            return $ret->$method($data);
        }
        throw new FQException('配置错误', -1);
    }
}