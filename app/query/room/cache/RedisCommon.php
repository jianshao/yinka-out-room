<?php


namespace app\query\room\cache;

use app\core\redis\Prefix;
use app\core\redis\Sharding;

/**
 * redis类
 */
class RedisCommon extends Sharding
{
    protected static $instance;
    protected $redisMap = [];
    protected $serviceName;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $object = new RedisCommon();
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
    public function getRedis($shardingColumn="",$arr = [])
    {
//        $dbName = $this->getDbName($shardingColumn);
//        $dbConfig = sprintf('cache.stores.%s', $dbName);
//        $redis_result = config($dbConfig);
//        return \app\common\RedisCommon::getInstance()->initConnectRedis($redis_result,$arr);
        return \app\common\RedisCommon::getInstance()->getRedis();
    }
}
