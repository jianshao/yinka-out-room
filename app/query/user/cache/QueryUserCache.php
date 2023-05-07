<?php

namespace app\query\user\cache;

use app\common\RedisCommon;
use app\query\user\elastic\UserModelElasticDao;

//QueryUserCache
class QueryUserCache
{
    protected static $instance;
    protected $redis = null;
    private $emptyData = "empty_zero";


    public function __construct(array $data = [])
    {
        $this->redis = RedisCommon::getInstance()->getRedis();
    }

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function getCacheKey($userId)
    {
        return sprintf(CachePrefix::$USER_INFO_CACHE, $userId);
    }


    /**
     * @param $search
     * @return string
     */
    public function getSearchUserForElasticKey($search)
    {
        return sprintf("%s_key:%s", CachePrefix::$search_elastic_user, md5($search));
    }

    /**
     * @param $search
     * @return array|false
     */
    public function getSearchUserForElasticCache($search)
    {
        $cacheKey = $this->getSearchUserForElasticKey($search);
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
            $result[] = UserModelElasticDao::getInstance()->dataToModel($data);
        }
        return $result;
    }


    public function getSearchUserForElasticLockKey($search)
    {
        return sprintf("%s_key:%s", CachePrefix::$search_elastic_user_lock, md5($search));
    }

    /**
     * @param $search
     */
    public function searchUserForElasticStoreZero($search)
    {
        $cacheKey = $this->getSearchUserForElasticKey($search);
        $this->redis->setex($cacheKey, CachePrefix::$search_elastic_user_empty_ttl, $this->emptyData);
    }


    public function searchUserForElasticStoreModel($search, $list){
        if (empty($search) || empty($list)) {
            return false;
        }
        $cacheKey = $this->getSearchUserForElasticKey($search);
        $datas = [];
        foreach ($list as $model) {
            $datas[] = UserModelElasticDao::getInstance()->modelToData($model);
        }
        $jsonStr = json_encode($datas);
        $this->redis->setex($cacheKey, CachePrefix::$search_elastic_user_data_ttl, $jsonStr);
        return true;
    }
}


