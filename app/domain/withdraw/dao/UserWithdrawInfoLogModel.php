<?php


namespace app\domain\withdraw\dao;


use app\core\mysql\ModelDao;
use app\domain\withdraw\model\UserWithdrawInfoModel;

//用户提现认证信息模型
class UserWithdrawInfoLogModel extends ModelDao
{
    protected $table = 'zb_user_withdraw_info_log';
    protected static $instance;
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function modelToData(UserWithdrawInfoModel $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->userId,
            'sns_user_id' => $model->snsUserId,
            'identity_card_front' => $model->identityCardFront,
            'identity_card_opposite' => $model->identityCardOpposite,
            'real_phone' => $model->realPhone,
            'real_name' => $model->realName,
            'identity_number' => $model->identityNumber,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime,
            'status' => $model->status,
            'message_detail' => $model->messageDetail,
        ];
    }

    /**
     * @param $data
     * @return UserWithdrawInfoModel
     */
    public function dataToModel($data)
    {
        $model = new UserWithdrawInfoModel();
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->snsUserId = $data['sns_user_id'];
        $model->identityCardFront = $data['identity_card_front'];
        $model->identityCardOpposite = $data['identity_card_opposite'];
        $model->realPhone = $data['real_phone'];
        $model->realName = $data['real_name'];
        $model->identityNumber = $data['identity_number'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        $model->status = $data['status'];
        $model->messageDetail = $data['message_detail'];
        return $model;
    }

    /**
     * @param UserWithdrawInfoModel $model
     * @return int|string
     */
    public function storeModel(UserWithdrawInfoModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

}