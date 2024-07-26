<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;
use app\domain\models\LoginDetailNewModel;
use app\utils\TimeUtil;

class LoginDetailNewModelDao extends ModelDao
{
    protected $serviceName = 'userMaster';
    protected $table = 'zb_login_detail_new';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LoginDetailNewModelDao();
        }
        return self::$instance;
    }

    public function modelToData(LoginDetailNewModel $model)
    {
        return [
            'user_id' => $model->userId,
            'ctime' => $model->loginTime,
            'channel' => $model->channel,
            'device_id' => $model->deviceId,
            'login_ip' => $model->loginIp,
            'mobile_version' => $model->device,
            'idfa' => $model->idfa,
            'version' => $model->version,
            'simulator' => (int)$model->simulator,
            'imei' => $model->imei,
            'app_id' => $model->appId,
            'source' => $model->source,
            'ext_param_1' => $model->ext_param_1,
            'ext_param_2' => $model->ext_param_2,
            'ext_param_3' => $model->ext_param_3,
            'ext_param_4' => $model->ext_param_4,
            'ext_param_5' => $model->ext_param_5,
        ];
    }

    public function add(LoginDetailNewModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($model->userId)->insertGetId($data);
    }

    //今天的登陆的次数
    public function getOneDayLoginNumber($userId, $timestamp)
    {
        $login_where = [];
        $login_where[] = ['user_id', '=', $userId];
        $login_where[] = ['ctime', '>=', TimeUtil::calcDayStartTimestamp($timestamp)];
        return $this->getModel($userId)->field('count(id) as number')->where($login_where)->count();
    }

    public function getLoginNumberByDevice($userId, $startTime, $endTime, $deviceId)
    {
        $login_where = [];
        $login_where[] = ['user_id', '=', $userId];
        $login_where[] = ['ctime', '>=', strtotime($startTime)];
        $login_where[] = ['ctime', '<', strtotime($endTime)];
        $login_where[] = ['device_id', '=', $deviceId];
        return $this->getModel($userId)->field('count(id) as number')->where($login_where)->count();
    }

    //在某段时间登陆的次数
    public function getLoginNumber($userId, $startTime, $endTime) {
        $login_where = [];
        $login_where[] = ['user_id','=', $userId];
        $login_where[] = ['ctime','>=',strtotime($startTime)];
        $login_where[] = ['ctime','<',strtotime($endTime)];
        return $this->getModel($userId)->field('count(id) as number')->where($login_where)->count();
    }
}