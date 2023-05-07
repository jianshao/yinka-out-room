<?php

namespace app\query\search\cache;

use app\common\RedisCommon;

class HotAnchorCache
{
    protected $pk = 'id';
    protected static $instance;


    public function __construct(array $data = [])
    {
        $this->redis = RedisCommon::getInstance()->getRedis();
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }



    private function getCacheKey()
    {
        return CachePrefix::$hotAnchorCache;
    }

    /**
     * @info 获取后台设置的前5名热门推荐主播
     */
    public function loadAllModelList()
    {
        $cacheKey = $this->getCacheKey();
        $data = $this->redis->ZREVRANGE($cacheKey, 0, 199);
        if (empty($data)) {
            return false;
        }
        return $data;
    }

}