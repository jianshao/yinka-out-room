<?php


namespace app\domain\task\dao;

use app\common\RedisCommon;

class WeekCheckinModelDao extends TaskModelDao
{
    protected $table = 'zb_task_checkin';
    protected $pk = 'uid';

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new WeekCheckinModelDao();
        }
        return self::$instance;
    }

    public function getPopCheckinTime($userId){
        $redis = RedisCommon::getInstance()->getRedis();

        $changeTime = $redis->HGET('weekcheckin_task_pop_status'.$userId, 'changeTime');
        return $changeTime ? $changeTime : 0;
    }

    public function updatePopChangeTime($userId, $timestamp){
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->HSET('weekcheckin_task_pop_status'.$userId, 'changeTime', $timestamp);
    }
}