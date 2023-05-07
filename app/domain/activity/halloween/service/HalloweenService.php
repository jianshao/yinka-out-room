<?php

namespace app\domain\activity\halloween\service;


use app\common\RedisCommon;
use app\domain\activity\halloween\config\Config;
use app\event\SendGiftEvent;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use Exception;
use think\facade\Log;

class HalloweenService
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new HalloweenService();
        }
        return self::$instance;
    }

    public function isExpire($timestamp=null){
        $config = Config::$CONF;
        $timestamp = $timestamp==null ? time(): $timestamp;
        $startTime = TimeUtil::strToTime(ArrayUtil::safeGet($config,'startTime','2022-10-31 00:00:00'));
        $endTime = TimeUtil::strToTime(ArrayUtil::safeGet($config,'stopTime','2022-10-31 23:59:59'));
        if ($timestamp < $startTime || $timestamp > $endTime){
            return true;
        }
        return false;
    }

    public function onSendGiftEvent(SendGiftEvent $event) {
        $time = $event->timestamp;
        if ($this->isExpire($time)){
            return;
        }
        if (in_array($event->giftKind->kindId, [641, 642, 623])){
            try {
                $userId = $event->fromUserId;
                $beanValue = intval($event->giftKind->price->count * $event->count * count($event->receiveUsers));
                $redis = RedisCommon::getInstance()->getRedis(["select" => 13]);
                //搞怪榜单 （日榜 总榜）
                $redis->zIncrBy($this->getRichDayRedisKey($time), $beanValue, $userId);
                $redis->zIncrBy($this->getRichAllRedisKey(), $beanValue, $userId);
                //可爱榜单 （日榜 总榜）
                $getValue = intval($event->giftKind->price->count * $event->count);
                foreach ($event->receiveDetails as list($receiveUser, $giftDetails)) {
                    $this->onRankLikeSetChange([$receiveUser->userId], $getValue, $time, $redis);
                }
            } catch (Exception $e) {
                Log::error(sprintf('HalloweenService onSendGiftEvent Exception userId=%d ex=%d:%s trace=%s',
                    $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
        }

        if ($event->timestamp >= 1667448000) {
            try {
                $userId = $event->fromUserId;
                $beanValue = intval($event->giftKind->price->count * $event->count * count($event->receiveUsers));
                $redis = RedisCommon::getInstance()->getRedis(["select" => 13]);
                //搞怪榜单 （日榜 总榜）
                $redis->zIncrBy($this->getRichDay1RedisKey($time), $beanValue, $userId);
                $redis->zIncrBy($this->getRichAll1RedisKey(), $beanValue, $userId);
                //可爱榜单 （日榜 总榜）
                $getValue = intval($event->giftKind->price->count * $event->count);
                foreach ($event->receiveDetails as list($receiveUser, $giftDetails)) {
                    $this->onRankLike1SetChange([$receiveUser->userId], $getValue, $time, $redis);
                }
            } catch (Exception $e) {
                Log::error(sprintf('HalloweenService onSendGiftEvent Exception userId=%d ex=%d:%s trace=%s',
                    $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
        }



    }

    //搞怪日榜
    public function getRichDayRedisKey($timestamp) {
        $day = date('Ymd',$timestamp);
        return sprintf('halloween_rich_day_%s', $day);
    }

    //搞怪总榜
    public function getRichAllRedisKey() {
        return 'halloween_rich_all';
    }


    public function onRankLikeSetChange($userIds, $value, $timestamp, $redis) {
        foreach ($userIds as $userId) {
            $redis->zIncrBy($this->getLikeDayRedisKey($timestamp), $value, $userId);
            $redis->zIncrBy($this->getLikeAllRedisKey(), $value, $userId);
        }
    }

    //搞怪日榜
    public function getLikeDayRedisKey($timestamp) {
        $day = date('Ymd',$timestamp);
        return sprintf('halloween_like_day_%s', $day);
    }

    //搞怪总榜
    public function getLikeAllRedisKey() {
        return 'halloween_like_all';
    }



    //搞怪日榜
    public function getRichDay1RedisKey($timestamp) {
        $day = date('Ymd',$timestamp);
        return sprintf('halloween_rich_day1_%s', $day);
    }

    //搞怪总榜
    public function getRichAll1RedisKey() {
        return 'halloween_rich_all1';
    }


    public function onRankLike1SetChange($userIds, $value, $timestamp, $redis) {
        foreach ($userIds as $userId) {
            $redis->zIncrBy($this->getLikeDay1RedisKey($timestamp), $value, $userId);
            $redis->zIncrBy($this->getLikeAll1RedisKey(), $value, $userId);
        }
    }

    //搞怪日榜
    public function getLikeDay1RedisKey($timestamp) {
        $day = date('Ymd',$timestamp);
        return sprintf('halloween_like_day1_%s', $day);
    }

    //搞怪总榜
    public function getLikeAll1RedisKey() {
        return 'halloween_like_all1';
    }




}