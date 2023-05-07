<?php

namespace app\query\weshine\cache;

use app\common\CacheRedis;
use app\query\weshine\model\ShineBlackKeywordModel;

// 闪萌黑名单缓存
class ShineBlackKeywordCache
{
    protected static $instance;
    private $redis;

    public function __construct(array $data = [])
    {
        $this->redis = CacheRedis::getInstance()->getRedis();
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new ShineBlackKeywordModel();
        $model->id = (int)$data['id'];
        $model->keyword = $data['keyword'];
        $model->keywordHash = $data['keyword_hash'];
        $model->createTime = (int)$data['create_time'];
        $model->adminId = (int)$data['admin_id'];
        return $model;
    }

    public function modelToData(ShineBlackKeywordModel $model)
    {
        return [
            'id' => $model->id,
            'keyword' => $model->keyword,
            'keyword_hash' => $model->keywordHash,
            'create_time' => $model->createTime,
            'admin_id' => $model->adminId,
        ];
    }

    private function getCacheKey($keyword)
    {
        return sprintf("%s_key:%s", CachePrefix::$shineBlackKeywordCache, md5($keyword));
    }

    public function getshineBlackLockKey($keyword){
        return sprintf("%s_key:%s", CachePrefix::$shineBlackKeywordLockKey, md5($keyword));
    }

    /**
     * @param $keyword
     * @return ShineBlackKeywordModel|false
     */
    public function loadModelForKeyword($keyword)
    {
        if (empty($keyword)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($keyword);
        $data = $this->redis->hGetAll($cacheKey);
        if (empty($data)) {
            return false;
        }
        return $this->dataToModel($data);
    }

    /**
     * @Info 设置缓存
     * @param $keyword
     * @param ShineBlackKeywordModel $model
     * @return bool
     */
    public function store($keyword,$model)
    {
        if (empty($keyword)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($keyword);
        $arrData = $this->modelToData($model);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, CachePrefix::$shineBlackKeywordCacheTtl);
        return $re;
    }


    /**
     * @Info 设置zero缓存
     * @param $keyword
     * @return bool
     */
    public function storeZero($keyword)
    {
        if (empty($keyword)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($keyword);
        $model = new ShineBlackKeywordModel;
        $arrData = $this->modelToData($model);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, CachePrefix::$shineBlackKeywordCacheZeroTtl);
        return $re;
    }


}


