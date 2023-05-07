<?php


namespace app\domain\game\taojin\dao;

use app\common\RedisCommon;

class TaoJinRankModelDao
{
    protected static $instance;
    protected $redis = null;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TaoJinRankModelDao();
            self::$instance->redis = RedisCommon::getInstance()->getRedis(["select" => 7]);
        }
        return self::$instance;
    }

    public static function getDayRedisKey($timestamp) {
        return 'day_rank_game'.date('Y-m-d', $timestamp);
    }

    public static function getWeekRedisKey($timestamp) {
        $week = date('Y-m-d 00:00:00',($timestamp-((date('w',$timestamp)==0?7:date('w',$timestamp))-1)*24*3600));
        return 'week_rank_game'.$week;
    }

    public static function getMonthRedisKey() {
        return 'month_rank_game'.date('Y-m-01');
    }

    public function getRankDataByRedisKey($redisKey, $start=0, $end=-1){
        return $this->redis->Zrevrange($redisKey, $start, $end, true);
    }

    public function setRankData($userId, $count, $timestamp) {
        $this->redis->zIncrBy(self::getDayRedisKey($timestamp), $count, $userId);
        $this->redis->zIncrBy(self::getWeekRedisKey($timestamp), $count, $userId);
        $this->redis->zIncrBy(self::getMonthRedisKey(), $count, $userId);
    }

    //删除过期的榜单 只处理日榜和周榜
    public function removeLastRank($timestamp) {

        $dayStamp = $timestamp-24*3600;
        //删除
        if ($this->redis->exists(self::getDayRedisKey($dayStamp))) {
            $this->redis->del(self::getDayRedisKey($dayStamp));
        }

        $weekStamp = $timestamp-7*24*3600;
        if ($this->redis->exists(self::getWeekRedisKey($weekStamp))) {
            $this->redis->del(self::getWeekRedisKey($weekStamp));
        }
    }
}
