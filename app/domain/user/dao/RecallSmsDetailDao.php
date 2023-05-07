<?php


namespace app\domain\user\dao;


use app\core\mysql\ModelDao;
use app\domain\user\model\RecallSmsDetailModel;


class RecallSmsDetailDao extends ModelDao
{
    protected $table = 'zb_recall_sms_detail_20211111';
    protected $serviceName = 'commonMaster';

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RecallSmsDetailDao();
        }
        return self::$instance;
    }

    /**
     * @param RecallSmsDetailModel $model
     * @return array
     */
    public function modelToData(RecallSmsDetailModel $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->userId,
            'send_gift' => $model->sendGift,
            'origin_login_time' => $model->originLoginTime,
            'sms_status' => $model->smsStatus,
            'sms_detail' => $model->smsDetail,
            'login_time' => $model->loginTime,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime
        ];
    }

    public function dataToModel($data)
    {
        $model = new RecallSmsDetailModel;
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->sendGift = $data['send_gift'];
        $model->originLoginTime = $data['origin_login_time'];
        $model->smsStatus = $data['sms_status'];
        $model->smsDetail = $data['sms_detail'];
        $model->loginTime = $data['login_time'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        return $model;
    }

    /**
     * @param RecallSmsDetailModel $model
     * @return int|string
     */
    public function storeData(RecallSmsDetailModel $model)
    {
        $item = $this->getModel($model->userId)->where('user_id', $model->userId)->limit(1)->find();
        if (!is_null($item)) {
            return 0;
        }

        $data = $this->modelToData($model);
        return $this->getModel($model->userId)->insert($data);
    }


    /**
     * @param $userId
     * @return RecallSmsDetailModel|null
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
        $model = $this->dataToModel($data);
        return $model;
    }

    /**
     * @param RecallSmsDetailModel $model
     */
    public function updateRecallData(RecallSmsDetailModel $model)
    {
        $data['update_time'] = $model->updateTime;
        $data['login_time'] = $model->loginTime;
        $data['send_gift'] = $model->sendGift;
        $this->getModel($model->userId)->where("user_id", $model->userId)->update($data);
    }
}