<?php

namespace app\domain\guild\cache;

//公会房间id zset bucket操作类
use app\common\RedisCommon;

class HomeHotRoomBucket
{
    private $redis_connect;

    public function __construct()
    {
        $this->redis_connect = RedisCommon::getInstance()->getRedis();
    }

    private function getBucketCacheKey()
    {
        return CachePrefix::$homeHotRoomSortBucket;
    }

    /**
     * @param $resetList HomeHotRoomCache[]
     * @return int  返回add的数量
     */
    public function storeList($resetList)
    {
        $cacheKey = $this->getBucketCacheKey();
        $score = 1;
        $this->clearBucket();
        foreach ($resetList as $itemModel) {
            $this->store($itemModel, $score);
            $score++;
        }
        $this->redis_connect->expire($cacheKey, CachePrefix::$bucketExpireTime);
        return $this->getBucketLen();
    }

    public function clearBucket()
    {
        $cacheKey = $this->getBucketCacheKey();
        $this->redis_connect->del($cacheKey);
    }

    public function getBucketLen()
    {
        $cacheKey = $this->getBucketCacheKey();
        return $this->redis_connect->zCard($cacheKey);
    }

    public function store(HomeHotRoomCache $itemModel, $score)
    {
        $cacheKey = $this->getBucketCacheKey();
        return $this->redis_connect->zAdd($cacheKey, $score, $itemModel->getRoomId());
    }


    /**
     * @info 获取 sore 降序排序后的数据list value
     * ZREVRANGE guild_hot_room_sort_bucket:0 0 10 WITHSCORES
     * @param $start int 开始节点  包含
     * @param $end int  结束节点  包含
     * @return array
     */
    public function getList($start, $end)
    {
        $cacheKey = $this->getBucketCacheKey();
        return $this->redis_connect->zRange($cacheKey, $start, $end, true);
    }

    public function getListCount()
    {
        $cacheKey = $this->getBucketCacheKey();
        return $this->redis_connect->zCard($cacheKey);
    }

    public function getListTotalPage($pageNum)
    {
        $total = $this->getListCount();
        return ceil($total / $pageNum);
    }


    /**
     * @info 获取房间bucket根据score 排序后的 ids list
     * @param $pageNum
     * @param $page
     * @return array
     */
    public function readPage($pageNum, $page)
    {
        $start = ($page - 1) * $pageNum;
        $end = $start + $pageNum - 1;
        $list = $this->getList($start, $end);
        $totalPage = $this->getListTotalPage($pageNum);
        return [$list, $totalPage];
    }


}


