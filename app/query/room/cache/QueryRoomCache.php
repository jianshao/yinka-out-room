<?php

namespace app\query\room\cache;

use app\common\RedisCommon;
use app\query\room\service\QueryRoomService;

class QueryRoomCache
{
    protected $pk = 'id';
    protected static $instance;

    private $emptyData = "empty_zero";

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


    /**
     * @param $search
     * @return string
     */
    public function getSearchRoomForElasticKey($search)
    {
        return sprintf("%s_key:%s", CachePrefix::$search_elastic_room, md5($search));
    }

    /**
     * @param $search
     * @return array|false
     */
    public function getSearchRoomForElasticCache($search)
    {
        $cacheKey = $this->getSearchRoomForElasticKey($search);
        $cacheString = $this->redis->get($cacheKey);
        if ($cacheString === false) {
            return false;
        }
        if ($cacheString === $this->emptyData) {
            return [];
        }

        $result = [];
        $datas = json_decode($cacheString, true);
        foreach ($datas as $data) {
            $result[] = QueryRoomService::getInstance()->dataToModel($data);
        }
        return $result;
    }

    /**
     * @param $search
     * @return string
     */
    public function getSearchRoomForElasticLockKey($search)
    {
        return sprintf("%s_key:%s", CachePrefix::$search_elastic_room_lock, md5($search));
    }

    /**
     * @param $search
     */
    public function searchRoomForElasticStoreZero($search)
    {
        $cacheKey = $this->getSearchRoomForElasticKey($search);
        $this->redis->setex($cacheKey, CachePrefix::$search_elastic_room_empty_ttl, $this->emptyData);
    }

    /**
     * @param $search
     * @param $list
     * @return bool
     */
    public function searchRoomForElasticStoreModel($search, $list)
    {
        if (empty($search) || empty($list)) {
            return false;
        }
        $cacheKey = $this->getSearchRoomForElasticKey($search);
        $datas = [];
        foreach ($list as $model) {
            $datas[] = QueryRoomService::getInstance()->modelToData($model);
        }
        $jsonStr = json_encode($datas);
        $this->redis->setex($cacheKey, CachePrefix::$search_elastic_room_data_ttl, $jsonStr);
        return true;
    }

}