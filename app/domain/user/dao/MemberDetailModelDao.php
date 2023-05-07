<?php

namespace app\domain\user\dao;

use app\core\mysql\ModelDao;
use app\domain\user\model\MemberDetailModel;

class MemberDetailModelDao extends ModelDao
{
    protected $table = 'zb_member_detail';
    protected static $instance;
    protected $serviceName = 'userMaster';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberDetailModelDao();
        }
        return self::$instance;
    }


    /**
     * @param $data
     * @return MemberDetailModel
     */
    public function dataToModel($data)
    {
        $model = new MemberDetailModel();
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->amount = $data['amount'];
        $model->oaid = $data['oaid'];
        $model->updateTime = $data['update_time'];
        $model->createTime = $data['create_time'];
        return $model;
    }

    public function modelTodata(MemberDetailModel $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->userId,
            'amount' => $model->amount,
            'oaid' => $model->oaid,
            'update_time' => $model->updateTime,
            'create_time' => $model->createTime,
        ];
    }

    /**
     * @param $userId
     * @param $count
     * @return \app\core\model\BaseModel|int
     */
    public function incrUserAmount($userId, $count)
    {
        if (empty($userId) || $count < 0) {
            return 0;
        }
        $unixTime = time();
        $result = $this->getModel($userId)->where(['user_id' => $userId])->inc('amount', $count)->update(['update_time' => $unixTime]);
        if ($result != 0) {
            return $result;
        }
        $model = new MemberDetailModel();
        $model->userId = $userId;
        $model->amount = $count;
        $model->createTime = $unixTime;
        $model->updateTime = $unixTime;
        $data = $this->modelTodata($model);
        return (int)$this->getModel($userId)->insertGetId($data);
    }


    /**
     * @param $uids
     * @param $chargeMax
     * @param $chargeMin
     * @return array
     */
    public function filterChargeUsers($uids, $chargeMax, $chargeMin)
    {
        $where = [];
        if ($chargeMax > 0) {
            $where[] = ['amount', "<=", $chargeMax];
        }
        if ($chargeMin > 0) {
            $where[] = ['amount', ">=", $chargeMin];
        }

        foreach($uids as $userId){
            $data[] = $this->getModel($userId)->where('user_id',$userId)->where($where)->column('user_id');
        }
        if (empty($data)) {
            return [];
        }
        return array_values($data);
    }


    /**
     * @param MemberDetailModel $model
     * @return int
     */
    public function storeModel(MemberDetailModel $model)
    {
        $data = $this->modelTodata($model);
        return (int)$this->getModel($model->userId)->insertGetId($data);
    }

    /**
     * @param $userId
     * @return MemberDetailModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForUserId($userId)
    {
        $defaultModel = new MemberDetailModel();
        if (empty($userId)) {
            return $defaultModel;
        }
        $model = $this->getModel($userId)->where('user_id', $userId)->find();
        if ($model === null) {
            return $defaultModel;
        }
        $data = $model->toArray();
        $model = $this->dataToModel($data);
        return $model;
    }

    public function getAmountByUserId($userId)
    {
        return $this->getModel($userId)->where(array("user_id" => $userId))->value("amount");
    }

    public function testStoreModel()
    {
        $unixTime = time();
        $model = new MemberDetailModel();
        $model->userId = 143988989;
        $model->amount = 0;
        $model->oaid = "sdjkd324923933kkkk";
        $model->createTime = $unixTime;
        $model->updateTime = 0;
        return MemberDetailModelDao::getInstance()->storeModel($model);
    }

}