<?php


namespace app\domain\rank\service;


use app\domain\exceptions\FQException;
use app\domain\rank\dao\RankModelDao;
use app\domain\version\cache\VersionCheckCache;
use think\facade\Log;

class RankService
{
    protected static $instance;

    private $rankType = [
        1 => 'Rich',
        2 => 'Like',
        3 => 'FansDevote'
    ];
    private $rankType1 = [
        1 => 'Rich7',
        2 => 'Like7',
        3 => 'FansDevote'
    ];

    private $cycleType = [
        1 => 'Day',
        2 => 'Week',
        3 => 'Month'
    ];

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RankService();
        }
        return self::$instance;
    }

    /**
     * 获取榜单
     * type 1:财富榜 2:魅力榜
     * roomId 大厅的榜传0 房间内的传房间的roomId
     * cycle 1:日榜 2：周榜 3：月榜
    */
    public function getRankData($type, $roomId, $cycle,$limit=50){
        try {
            //日周月榜
            switch ($cycle) {
                case 1:
                    $time = date("Ymd",time());
                    break;
                case 2:
                    $time = date('Ymd',(time()-((date('w',time())==0?7:date('w',time()))-1)*24*3600));
                    break;
                case 3:
                    $time = date("Ym",mktime(00,00,00,date("m"),date("t"),date("Y")));
                    break;
                default:
                    break;
            }
            if (($cycle == 3 || $cycle == 2) && $roomId != 0) {
                $redisKey = sprintf('%s_%s_%s_%s', $this->rankType1[$type], $this->cycleType[$cycle], strval($roomId), $time);
            } else {
                $redisKey = sprintf('%s_%s_%s_%s', $this->rankType[$type], $this->cycleType[$cycle], strval($roomId), $time);
            }
            $end=$limit-1;
            $rankData = RankModelDao::getInstance()->getRankDataByRedisKey($redisKey,0,$end);
            Log::debug(sprintf('getRankData getList redisKey=%s data=%s', $redisKey, json_encode($rankData)));
            return $rankData;
        }catch (FQException $e) {
            throw $e;
        }
    }

    /**
     * @info app提审中 获取榜单
     * @param $type 1:财富榜 2:魅力榜
     * @param $roomId 1:大厅的榜传0 房间内的传房间的roomId
     * @param $cycle 1:日榜 2：周榜 3：月榜
     * @return mixed
     * @throws
     */
    public function getVersionRankData($type, $roomId,$cycle,$limit=50){

        try {
            $redisKey = sprintf(VersionCheckCache::$rankListKey, $this->rankType[$type], $this->cycleType[$cycle], strval($roomId), 0);
            $end=$limit-1;
            $rankData = RankModelDao::getInstance()->getVersionRankDataByRedisKey($redisKey,0,$end);
            Log::debug(sprintf('getRankData getList redisKey=%s data=%s', $redisKey, json_encode($rankData)));
            return $rankData;
        }catch (FQException $e) {
            throw $e;
        }
    }

    /**
     * 获取粉丝榜单
     */
    public function getFansRankData($type, $uid, $cycle){
        try {
            //日周月榜
            switch ($cycle) {
                case 1:
                    $time = date("Ymd",time());
                    break;
                case 2:
                    $time = date('Ymd',(time()-((date('w',time())==0?7:date('w',time()))-1)*24*3600));
                    break;
                case 3:
                    $time = date("Ym",mktime(00,00,00,date("m"),date("t"),date("Y")));
                    break;
                default:
                    break;
            }

            $redisKey = sprintf('%s_%s_%s_%s', $this->rankType[$type], $this->cycleType[$cycle], strval($uid), $time);
            $rankData = RankModelDao::getInstance()->getRankDataByRedisKey($redisKey);
            Log::debug(sprintf('getRankData getList redisKey=%s data=%s', $redisKey, json_encode($rankData)));
            return $rankData;
        }catch (FQException $e) {
            throw $e;
        }
    }


    //财富榜变化
    public function onRankRickChange($userId, $roomId, $value, $timestamp)
    {
        RankModelDao::getInstance()->setRickRankData($userId, $roomId, $value, $timestamp);
        Log::record(sprintf('RankService setRickRankData userId=%d roomId=%d count=%d time=%d', $userId, $roomId, $value, $timestamp));
//        RankModelDao::getInstance()->removeLastRickRank($roomId, $timestamp);
    }

    //魅力榜变化
    public function onRankLikeChange($userIds, $roomId, $value, $timestamp)
    {
        try {
            foreach ($userIds as $userId){
                $this->onRankLikeChangeImpl($userId, $roomId, $value, $timestamp);
            }
        }catch (FQException $e) {
            throw $e;
        }
    }

    //魅力榜变化
    public function onRankLikeChangeImpl($userId, $roomId, $value, $timestamp)
    {
        RankModelDao::getInstance()->setLikeRankData($userId, $roomId, $value, $timestamp);
        Log::record(sprintf('RankService setLikeRankData userId=%d roomId=%d count=%d time=%d', $userId, $roomId, $value, $timestamp));
//        RankModelDao::getInstance()->removeLastLikeRank($roomId, $timestamp);
    }

    //粉丝贡献榜变化
    public function onRankFansDevoteChange($fromUid, $userIds, $value, $timestamp) {
        try {
            foreach ($userIds as $userId){
                $this->onRankFansDevoteChangeImpl($fromUid, $userId, $value, $timestamp);
            }
        }catch (FQException $e) {
            throw $e;
        }
    }
    //粉丝贡献榜变化
    public function onRankFansDevoteChangeImpl($fromUid, $userId, $value, $timestamp) {
        RankModelDao::getInstance()->setFansDevoteRankData($fromUid, $userId, $value, $timestamp);
        Log::record(sprintf('RankService setFansDevoteRankData fromUId=%d userId=%d count=%d time=%d', $fromUid, $userId, $value, $timestamp));
    }

}