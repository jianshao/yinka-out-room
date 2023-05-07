<?php

namespace app\domain\dao;

use app\common\CacheRedis;
use app\core\mysql\ModelDao;
use app\domain\models\MonitoringModel;
use app\utils\TimeUtil;


class MonitoringModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_monitoring';
    protected $pk = 'monitoring_id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MonitoringModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new MonitoringModel();
        $model->userId = $data['user_id'];
        $model->monitoringId = $data['monitoring_id'];
        $model->monitoringPassword = $data['monitoring_pwd'];
        $model->monitoringStatus = $data['monitoring_status'];
        $model->parentsPassword = $data['parents_pwd'];
        $model->parentStatus = $data['parents_status'];
        $model->monitoringTime = $data['monitoring_time'] == '0000-00-00 00:00:00' ? 0 : TimeUtil::strToTime($data['monitoring_time']);
        $model->monitoringEndTime = $data['monitoring_endtime'];
        $model->lockTime = $data['lock_time'] == '0000-00-00 00:00:00' ? 0 : TimeUtil::strToTime($data['lock_time']);
        $model->constraintLock = $data['constraint_lock'];
        $model->status = $data['status'];
        return $model;
    }

    public function modelToData($model)
    {
        return [
            'user_id' => $model->userId,
            'monitoring_pwd' => $model->monitoringPassword,
            'pwd' => $model->password,
            'monitoring_status' => $model->monitoringStatus,
            'parents_pwd' => $model->parentsPassword,
            'parents_status' => $model->parentStatus,
            'monitoring_time' => TimeUtil::timeToStr($model->monitoringTime),
            'monitoring_endtime' => $model->monitoringEndTime,
            'lock_time' => TimeUtil::timeToStr($model->lockTime),
            'constraint_lock' => $model->constraintLock,
            'status' => $model->status
        ];
    }

    private function cacheOneUserKey($userId)
    {
        return sprintf("MonitoringModelDaoForUserId:%d", $userId);
    }

    /**
     * @param $userId
     * @param false $cache
     * @return MonitoringModel|array|mixed|null
     */
    public function findByUserId($userId, $cache = false)
    {
        if (empty($userId)) {
            return null;
        }
        if ($cache) {
            $data = $this->findUserForCache($userId);
        } else {
            $data = $this->findUserForDb($userId);
        }
        if (empty($data)){
            return $data;
        }
        return $this->dataToModel($data);
    }

    private function findUserForDb($userId)
    {
        $data = $this->getModel()->where(['user_id' => $userId])->find();
        if (empty($data)) {
            return null;
        }
        return $data->toArray();
    }

    private function findUserForCache($userId)
    {
        $cacheKey = $this->cacheOneUserKey($userId);
        $redis = CacheRedis::getInstance()->getRedis();

        $cacheData = $redis->get($cacheKey);
        if (!empty($cacheData)) {
            if ($cacheData == "notfind") {
                return null;
            }
            return json_decode($cacheData, true);
        }
        //todo check
        $data = $this->getModel()->where(['user_id' => $userId])->find();
        if (empty($data)) {
            $redis->setex($cacheKey, 2, "notfind");
            return null;
        }
        $redis->setex($cacheKey, 10, json_encode($data->toArray()));
        return $data->toArray();
    }

    public function issetUserId($userId)
    {
        $data = $this->getModel()->where(['user_id' => $userId])->field('monitoring_id')->find();
        if (empty($data)) {
            return null;
        }
        return true;
    }

    public function insertModel($model)
    {
        $data = $this->modelToData($model);
        $this->getModel()->insert($data);
    }

    public function updateMonitoringEndTime($userId, $monitoringEndTime)
    {
        $this->getModel()->where(['user_id' => $userId])->update([
            'monitoring_endtime' => $monitoringEndTime
        ]);
        $redis = CacheRedis::getInstance()->getRedis();
        $redis->del($this->cacheOneUserKey($userId));
    }

    public function updateMonitoringPassword($userId, $monitoringPassword, $password)
    {
        $this->getModel()->where(['user_id' => $userId])->update([
            'monitoring_pwd' => $monitoringPassword,
            'pwd' => $password
        ]);
        $redis = CacheRedis::getInstance()->getRedis();
        $redis->del($this->cacheOneUserKey($userId));
    }

    public function updateStatus($userId, $status)
    {
        $this->getModel()->where(['user_id' => $userId])->update([
            'status' => $status
        ]);
        $redis = CacheRedis::getInstance()->getRedis();
        $redis->del($this->cacheOneUserKey($userId));
    }

    public function removeByUserId($userId)
    {
        //todo check
        $re = $this->getModel()->where(['user_id' => $userId])->delete();
        $redis = CacheRedis::getInstance()->getRedis();
        $redis->del($this->cacheOneUserKey($userId));
        return $re;
    }
}