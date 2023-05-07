<?php


namespace app\common;

use app\core\redis\Prefix;
use app\core\redis\Sharding;

/**
 * redis类
 */
class RedisDefault extends Sharding
{
    protected static $instance;
    protected $redisMap = [];
    protected $serviceName;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $object = new RedisDefault();
            $object->serviceName = Prefix::$ROOM;
            self::$instance = $object;
        }
        return self::$instance;
    }

    public function buildRedisKey($conf)
    {
        return sprintf('%s:%d:%d', $conf['host'], $conf['port'], $conf['select']);
    }


    /**
     * @info 获取redis实例
     * @param array $arr
     * @return mixed|\Redis
     */
    public function getRedis($shardingColumn = "", $arr = [])
    {
        return \app\common\RedisCommon::getInstance()->getRedis();
    }
}
