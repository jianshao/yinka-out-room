<?php


namespace app\domain\rank\dao;

use app\common\RedisCommon;

class RankModelDao
{
    protected static $instance;
    protected $redis = null;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RankModelDao();
            self::$instance->redis = RedisCommon::getInstance()->getRedis(["select" => 1]);
        }
        return self::$instance;
    }

    public static function getRickDayRedisKey($roomId, $timestamp) {
        $day = date('Ymd',$timestamp);
        return sprintf('Rich_Day_%s_%s', strval($roomId), $day);
    }

    public static function getRickWeekRedisKey($roomId, $timestamp) {
        $week = date('Ymd',($timestamp-((date('w',$timestamp)==0?7:date('w',$timestamp))-1)*24*3600));
        return sprintf('Rich_Week_%s_%s', strval($roomId), $week);
    }

    public static function getRickMonthRedisKey($roomId, $timestamp) {
        $month = date('Ym',mktime(00,00,00,date('m'),date('t'),date('Y')));
        return sprintf('Rich_Month_%s_%s', strval($roomId), $month);
    }

    //todo: delete
    public static function getRick1MonthRedisKey($roomId, $timestamp) {
        $month = date('Ym',mktime(00,00,00,date('m'),date('t'),date('Y')));
        return sprintf('Rich1_Month_%s_%s', strval($roomId), $month);
    }
    //todo: delete
    public static function getRick7WeekRedisKey($roomId, $timestamp) {
        $week = date('Ymd',($timestamp-((date('w',$timestamp)==0?7:date('w',$timestamp))-1)*24*3600));
        return sprintf('Rich7_Week_%s_%s', strval($roomId), $week);
    }
    //todo: delete
    public static function getRick7MonthRedisKey($roomId, $timestamp) {
        $month = date('Ym',mktime(00,00,00,date('m'),date('t'),date('Y')));
        return sprintf('Rich7_Month_%s_%s', strval($roomId), $month);
    }


    public static function getLikeDayRedisKey($roomId, $timestamp) {
        $day = date('Ymd',$timestamp);
        return sprintf('Like_Day_%s_%s', strval($roomId), $day);
    }

    public static function getLikeWeekRedisKey($roomId, $timestamp) {
        $week = date('Ymd',($timestamp-((date('w',$timestamp)==0?7:date('w',$timestamp))-1)*24*3600));
        return sprintf('Like_Week_%s_%s', strval($roomId), $week);
    }

    public static function getLikeMonthRedisKey($roomId, $timestamp) {
        $month = date('Ym',mktime(00,00,00,date('m'),date('t'),date('Y')));
        return sprintf('Like_Month_%s_%s', strval($roomId), $month);
    }

    //todo:delete
    public static function getLike1MonthRedisKey($roomId, $timestamp) {
        $month = date('Ym',mktime(00,00,00,date('m'),date('t'),date('Y')));
        return sprintf('Like1_Month_%s_%s', strval($roomId), $month);
    }

    //todo:delete
    public static function getLike7WeekRedisKey($roomId, $timestamp) {
        $week = date('Ymd',($timestamp-((date('w',$timestamp)==0?7:date('w',$timestamp))-1)*24*3600));
        return sprintf('Like7_Week_%s_%s', strval($roomId), $week);
    }

    //todo:delete
    public static function getLike7MonthRedisKey($roomId, $timestamp) {
        $month = date('Ym',mktime(00,00,00,date('m'),date('t'),date('Y')));
        return sprintf('Like7_Month_%s_%s', strval($roomId), $month);
    }


    public static function getFansDevoteDayRedisKey($uid, $timestamp) {
        $day = date('Ymd',$timestamp);
        return sprintf('FansDevote_Day_%s_%s', strval($uid), $day);
    }

    public static function getFansDevoteWeekRedisKey($uid, $timestamp) {
        $week = date('Ymd',($timestamp-((date('w',$timestamp)==0?7:date('w',$timestamp))-1)*24*3600));
        return sprintf('FansDevote_Week_%s_%s', strval($uid), $week);
    }

    public static function getFansDevoteMonthRedisKey($uid, $timestamp) {
        $month = date('Ym',mktime(00,00,00,date('m'),date('t'),date('Y')));
        return sprintf('FansDevote_Month_%s_%s', strval($uid), $month);
    }

    public function getRankDataByRedisKey($redisKey, $start=0, $end=-1){
        return $this->redis->ZREVRANGE($redisKey, $start, $end, true);
    }

    public function getVersionRankDataByRedisKey($redisKey, $start=0, $end=-1){
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->ZREVRANGE($redisKey, $start, $end, true);
    }

    /**
     * $roomId 大厅的榜传0 房间内的传房间的roomId
     * */
    public function setLikeRankData($userId, $roomId, $count, $timestamp) {
        $this->redis->zIncrBy(self::getLikeDayRedisKey('0', $timestamp), $count, $userId);
        $this->redis->zIncrBy(self::getLikeWeekRedisKey('0', $timestamp), $count, $userId);
        $this->redis->zIncrBy(self::getLikeMonthRedisKey('0', $timestamp), $count, $userId);

        if($roomId>0){
            $this->redis->zIncrBy(self::getLikeDayRedisKey($roomId, $timestamp), $count, $userId);
            $this->redis->zIncrBy(self::getLikeWeekRedisKey($roomId, $timestamp), $count, $userId);
            $this->redis->zIncrBy(self::getLikeMonthRedisKey($roomId, $timestamp), $count, $userId);
            if (time() >= 1618218000) {
                $this->redis->zIncrBy(self::getLike1MonthRedisKey($roomId, $timestamp), $count, $userId);
            }
            if (time() >= 1618541700) {
                $this->redis->zIncrBy(self::getLike7WeekRedisKey($roomId, $timestamp), $count, $userId);
                $this->redis->zIncrBy(self::getLike7MonthRedisKey($roomId, $timestamp), $count, $userId);
            }
        }
    }

    /**
     * $roomId 大厅的榜传0 房间内的传房间的roomId
     * */
    public function setRickRankData($userId, $roomId, $count, $timestamp) {
        $this->redis->zIncrBy(self::getRickDayRedisKey('0', $timestamp), $count, $userId);
        $this->redis->zIncrBy(self::getRickWeekRedisKey('0', $timestamp), $count, $userId);
        $this->redis->zIncrBy(self::getRickMonthRedisKey('0', $timestamp), $count, $userId);

        if($roomId>0){
            $this->redis->zIncrBy(self::getRickDayRedisKey($roomId, $timestamp), $count, $userId);
            $this->redis->zIncrBy(self::getRickWeekRedisKey($roomId, $timestamp), $count, $userId);
            $this->redis->zIncrBy(self::getRickMonthRedisKey($roomId, $timestamp), $count, $userId);
            if (time() >= 1618218000) {
                $this->redis->zIncrBy(self::getRick1MonthRedisKey($roomId, $timestamp), $count, $userId);
            }
            if (time() >= 1618541700) {
                $this->redis->zIncrBy(self::getRick7WeekRedisKey($roomId, $timestamp), $count, $userId);
                $this->redis->zIncrBy(self::getRick7MonthRedisKey($roomId, $timestamp), $count, $userId);
            }
        }
    }

    /**
     * 粉丝贡献榜
     */
    public function setFansDevoteRankData($fromUid, $userId, $count, $timestamp) {
        $this->redis->zIncrBy(self::getFansDevoteDayRedisKey($userId, $timestamp), $count, $fromUid);
        $this->redis->zIncrBy(self::getFansDevoteWeekRedisKey($userId, $timestamp), $count, $fromUid);
        $this->redis->zIncrBy(self::getFansDevoteMonthRedisKey($userId, $timestamp), $count, $fromUid);
    }

    //删除过期的榜单 只处理日榜和周榜
    public function removeLastRickRank($roomId, $timestamp) {

        $dayStamp = $timestamp-24*3600;
        //删除
        if ($this->redis->exists(self::getRickDayRedisKey('0', $dayStamp))) {
            $this->redis->del(self::getRickDayRedisKey('0', $dayStamp));
        }
        if ($this->redis->exists(self::getRickDayRedisKey($roomId, $dayStamp))) {
            $this->redis->del(self::getRickDayRedisKey($roomId, $dayStamp));
        }

        $weekStamp = $timestamp-7*24*3600;
        if ($this->redis->exists(self::getRickWeekRedisKey('0', $weekStamp))) {
            $this->redis->del(self::getRickWeekRedisKey('0', $weekStamp));
        }
        if ($this->redis->exists(self::getRickWeekRedisKey($roomId, $weekStamp))) {
            $this->redis->del(self::getRickWeekRedisKey($roomId, $weekStamp));
        }
    }

    //删除过期的榜单 只处理日榜和周榜
    public function removeLastLikeRank($roomId, $timestamp) {

        $dayStamp = $timestamp-24*3600;
        //删除
        if ($this->redis->exists(self::getLikeDayRedisKey('0', $dayStamp))) {
            $this->redis->del(self::getLikeDayRedisKey('0', $dayStamp));
        }
        if ($this->redis->exists(self::getLikeDayRedisKey($roomId, $dayStamp))) {
            $this->redis->del(self::getLikeDayRedisKey($roomId, $dayStamp));
        }

        $weekStamp = $timestamp-7*24*3600;
        if ($this->redis->exists(self::getLikeWeekRedisKey('0', $weekStamp))) {
            $this->redis->del(self::getLikeWeekRedisKey('0', $weekStamp));
        }
        if ($this->redis->exists(self::getLikeWeekRedisKey($roomId, $weekStamp))) {
            $this->redis->del(self::getLikeWeekRedisKey($roomId, $weekStamp));
        }
    }
}
