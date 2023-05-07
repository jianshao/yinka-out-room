<?php

namespace app\domain\dao;

use app\core\mysql\ModelDao;
use app\domain\exceptions\FQException;
use app\domain\models\UserIdentityModel;
use app\domain\models\UserIdentityStatusModel;

class UserCardModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_user_card';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * @return UserIdentityModel
     */
    public function dataToModel($data)
    {
        $model = new UserIdentityModel($data['uid']);
        $model->certName = $data['name'];
        $model->certno = $data['idCard'];
        $model->outerOrderNo = "";
        $model->certifyid = "";
        $model->createTime = $data['create_time'];
        $model->status = $data['status'];
        return $model;
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
        $object = $this->getModel()->where($where)->order("id", "desc")->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
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


}