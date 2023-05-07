<?php

namespace app\domain\user\dao;

use app\common\RedisCommon;
use app\domain\user\model\ActiveDegreeModel;

class ActiveDegreeModelDao
{
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ActiveDegreeModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        return new ActiveDegreeModel($data['day'], $data['week'], $data['updateTime']);
    }

    public function loadActiveDegree($userId) {
        $redis = RedisCommon::getInstance()->getRedis();

        $data = $redis->HMGET('active_degree_'.$userId, array('day', 'week', 'updateTime'));
        return $data ? $this->dataToModel($data):null;
    }

    public function saveActiveDegree($userId, $model) {
        $redis = RedisCommon::getInstance()->getRedis();
        $data = array('day'=>$model->day, 'week'=>$model->week, 'updateTime'=>$model->updateTime);
        $redis->HMSET('active_degree_'.$userId, $data);
    }

    public function loadDayActiveDegree($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->HGET('active_degree_'.$userId, 'day');
    }

    public function loadWeekActiveDegree($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->HGET('active_degree_'.$userId, 'week');
    }
}