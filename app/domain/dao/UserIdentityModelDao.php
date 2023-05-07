<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;
use app\domain\exceptions\FQException;
use app\domain\models\UserIdentityModel;
use app\domain\models\UserIdentityStatusModel;

class UserIdentityModelDao extends ModelDao {
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_user_identity';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserIdentityModelDao();
        }
        return self::$instance;
    }

    //获取身份证认证成功数量
    public function getCountByCertno($certNo) {
        return $this->getModel()->where(['certno' => $certNo, 'status' => 1])->group('uid')->count();
    }


    public function loadByCertifyId($certifyId) {
        return $this->loadByWhere(['certifyid' => $certifyId]);
    }

    /**
     * @return UserIdentityModel
     */
    public function loadByWhere($where) {
        $data = $this->getModel()->where($where)->order('id desc')->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * 状态0：失败 1：成功 2：待确认
     * @param $userId
     * @return UserIdentityModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForUserId($userId)
    {
        $where[] = ['uid', '=', $userId];
        $where[] = ['status', '=', 1];
        $data = $this->getModel()->where($where)->order("id", "desc")->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * @param $userId
     * @param $status
     * @return \app\core\model\BaseModel|int
     * @throws FQException
     */
    public function resetIdentityStatus($userId,$status)
    {
        $where[] = ['uid', '=', $userId];
        $where[] = ['status', '=', UserIdentityStatusModel::$SUCCESS];
        $tempArr= $this->getModel()->where($where)->order("id", "desc")->field("id")->limit(1)->column("id");
        if (empty($tempArr)) {
            return 0;
        }
        $pkId=current($tempArr);
        $updateWhere['id']=$pkId;
        $data['status']=$status;
        return $this->getModel()->where($updateWhere)->update($data);
    }

    /**
     * @return UserIdentityModel
     */
    public function dataToModel($data)
    {
        $model = new UserIdentityModel($data['uid']);
        $model->certName = $data['certname'];
        $model->certno = $data['certno'];
        $model->outerOrderNo = $data['outer_order_no'];
        $model->certifyid = $data['certifyid'];
        $model->createTime = $data['create_time'];
        $model->status = $data['status'];
        return $model;
    }

    public function modelToData($model)
    {
        return [
            'uid' => $model->userId,
            'certname' => $model->certName,
            'certno' => $model->certno,
            'outer_order_no' => $model->outerOrderNo,
            'certifyid' => $model->certifyid,
            'create_time' => $model->createTime,
            'status' => $model->status
        ];
    }

    /**
     * @param  $model UserIdentityModel
     */
    public function addData($model) {
        $data = [
            'uid' => $model->userId,
            'certname' => $model->certName,
            'certno' => $model->certno,
            'outer_order_no' => $model->outerOrderNo,
            'certifyid' => $model->certifyid,
            'create_time' => $model->createTime,
            'status' => $model->status
        ];
        return $this->getModel()->save($data);
    }

}