<?php

namespace app\domain\user\dao;

use app\core\mysql\ModelDao;
use app\domain\user\model\MemberRecallModel;

/**
 * @info 用户召回(长期)统计Dao
 * Class MemberRecallModelDao
 * @package app\domain\user\dao
 */
class MemberRecallModelDao extends ModelDao
{
    protected $table = 'zb_member_recall_detail';

    protected static $instance;
    protected $serviceName = 'commonMaster'; # 后台不让分库

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberRecallModelDao();
        }
        return self::$instance;
    }


    /**
     * @param MemberRecallModel $model
     * @return array
     */
    public function modelToData(MemberRecallModel $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->userId,
            'origin_login_time' => $model->originLoginTime,
            'charge_status' => $model->chargeStatus,
            'amount' => $model->amount,
            'free_coin' => $model->freeCoin,
            'coin_balance' => $model->coinBalance,
            'sns_response' => $model->snsResponse,
            'login_time' => $model->loginTime,
            'recall_login_status' => $model->recallLoginStatus,
            'mobile' => $model->mobile,
            'type' => $model->type,
            'push_when_time' => $model->pushWhenTime,
            'sns_id' => $model->snsId,
            'sns_confirm' => $model->snsConfirm,
            'str_date' => $model->strDate,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime
        ];
    }

    public function dataToModel($data)
    {
        $model = new MemberRecallModel;
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->originLoginTime = $data['origin_login_time'];
        $model->chargeStatus = $data['charge_status'];
        $model->amount = $data['amount'];
        $model->freeCoin = $data['free_coin'];
        $model->coinBalance = $data['coin_balance'];
        $model->snsResponse = $data['sns_response'];
        $model->loginTime = $data['login_time'];
        $model->recallLoginStatus = $data['recall_login_status'];
        $model->mobile = $data['mobile'];
        $model->type = $data['type'];
        $model->pushWhenTime = $data['push_when_time'];
        $model->snsId = $data['sns_id'];
        $model->snsConfirm = $data['sns_confirm'];
        $model->strDate = $data['str_date'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        return $model;
    }

    /**
     * @param MemberRecallModel $model
     * @return int|string
     */
    public function storeData(MemberRecallModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($model->userId)->insertGetId($data);
    }


    /**
     * @info
     * @param $userId
     * @return MemberRecallModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function findUserModel($userId)
    {
        $obj = $this->getModel($userId)->where("user_id", $userId)->limit(1)->find();
        if (empty($obj)) {
            return null;
        }
        $data = $obj->toArray();
        return $this->dataToModel($data);
    }


    /**
     * @info 找用户的最后一次召回信息
     * @param $userId
     * @return MemberRecallModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function findUserLastModel($userId)
    {
        $obj = $this->getModel($userId)->where("user_id", $userId)->order("id desc")->limit(1)->find();
        if ($obj === null) {
            return null;
        }
        $data = $obj->toArray();
        return $this->dataToModel($data);
    }


    /**
     * @info 标记用户召回成功
     * @param MemberRecallModel $model
     * @param $unixTime
     */
    public function updateRecallData(MemberRecallModel $model, $unixTime)
    {
        $where[] = ['id', '=', $model->id];
        $where[] = ['recall_login_status', '=', 0];
        $data['update_time'] = $unixTime;
        $data['recall_login_status'] = $model->recallLoginStatus;
        $data['login_time'] = $model->loginTime;
        return $this->getModel($model->userId)->where($where)->update($data);
    }
}