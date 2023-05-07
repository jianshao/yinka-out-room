<?php


namespace app\common;

use app\utils\ArrayUtil;
use think\App;
use think\facade\Log;

/**
 * redis类 操作用户数据缓存
 */
class CacheRedis
{
    protected static $instance;
    protected $redisMap = [];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CacheRedis();
        }
        return self::$instance;
    }

    public function buildRedisKey($conf) {
        return sprintf('%s:%d:%d', $conf['host'], $conf['port'], $conf['select']);
    }

    //获取redis实例
    public function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.cacheredis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = ArrayUtil::safeGet($arr, 'select', 0);
        $key = $this->buildRedisKey($param);
        $handler = ArrayUtil::safeGet($this->redisMap, $key);
        Log::info(sprintf('CacheRedis::getRedis key=%s handlerExists=%d', $key, $handler != null));
        if ($handler == null) {
            if (!empty($arr)) {
                foreach ($arr as $k => $v) {
                    $param[$k] = $v;
                }
            }
            $handler = new \Redis;
            $handler->connect($param['host'], $param['port'], 0);
            if ('' != $param['password']) {
                $handler->auth($param['password']);
            }

            if (0 != $param['select']) {
                $handler->select($param['select']);
            }

            $this->redisMap[$key] = $handler;
        }
        return $handler;
    }
}
