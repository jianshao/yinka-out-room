<?php
/**
 * User: yond
 * Date: 2020
 * 认证表
 */
namespace app\domain\user\dao;
use app\core\mysql\ModelDao;
use app\domain\user\model\UserBlackModel;


class UserBlackModelDao extends ModelDao {

    protected $serviceName = 'commonMaster';
    protected $table = 'zb_black_data';
    protected static $instance;
    protected $autoWriteTimestamp = false;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserBlackModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data) {
        $model = new UserBlackModel($data['user_id']);
        $model->status = $data['status'];
        $model->blackinfo = $data['blackinfo'];
        $model->adminId = $data['admin_id'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        $model->time = $data['time'];
        $model->endTime = $data['end_time'];
        $model->reason = $data['reason'];
        $model->blackTime = $data['blacks_time'];
        return $model;
    }

    /*
     * return UserBlackModel
     * */
    public function loadData($userId) {
        $data = $this->getModel()->where(['user_id' => $userId])->find();
        if (empty($data)) {
            return null;
        }

        return $this->dataToModel($data);
    }

    public function addData($data) {
        return $this->getModel()->save($data);
    }

    public function isBlockWithDeviceId($deviceId) {
        $where[] = ['blackinfo' ,'=', $deviceId];
        $where[] = ['status', '=',  1];
        $where[] = ['type', '=', 2];
        $deviceIdBlack = $this->getModel()->where($where)->find();
        if (empty($deviceIdBlack)) {
            return null;
        }
        return $this->dataToModel($deviceIdBlack->toArray());
    }

    public function isBlockWithIp($ip) {
        $where[] = ['blackinfo' ,'=', $ip];
        $where[] = ['status', '=',  1];
        $where[] = ['type', '=', 1];
        $ipBlack = $this->getModel()->where($where)->find();
        if (empty($ipBlack)) {
            return null;
        }
        return $this->dataToModel($ipBlack->toArray());
    }

    public function isBlockWithCertNo($certNo) {
        $where[] = ['blackinfo' ,'=', $certNo];
        $where[] = ['status', '=',  1];
        $where[] = ['type', '=', 3];
        $certBlack = $this->getModel()->where($where)->find();
        if (empty($certBlack)) {
            return null;
        }
        return $this->dataToModel($certBlack->toArray());
    }

    public function isBlockWithUser($userId) {
        $where[] = ['user_id' ,'=', $userId];
        $where[] = ['status', '=', 1];
        $where[] = ['type', '=', 4];
        $idBlack = $this->getModel()->where($where)->find();
        if (empty($idBlack)) {
            return null;
        }
        return $this->dataToModel($idBlack->toArray());
    }

    public function isBlockWithImei($imei) {
        $where[] = ['blackinfo' ,'=', $imei];
        $where[] = ['status', '=',  1];
        $where[] = ['type', '=', 5];
        $imeiBlack = $this->getModel()->where($where)->find();
        if (empty($imeiBlack)) {
            return null;
        }
        return $this->dataToModel($imeiBlack->toArray());
    }

}