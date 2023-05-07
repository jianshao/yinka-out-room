<?php


namespace app\domain\withdraw\dao;


use app\core\mysql\ModelDao;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\withdraw\model\UserWithdrawBankInformationHover;
use app\domain\withdraw\model\UserWithdrawBankInformationModel;
use app\domain\withdraw\model\UserWithdrawBankInformationPayType;


//用户提现账号信息
class UserWithdrawBankInformationModelDao extends ModelDao
{
    protected $table = 'zb_user_withdraw_bank_information';
    protected static $instance;
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    public static $VERIFY_STATUS_AUDIT = 0;
    public static $VERIFY_STATUS_SUCCESS = 1;
    public static $VERIFY_STATUS_ERROR = 2;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserWithdrawBankInformationModelDao();
        }
        return self::$instance;
    }

    public function modelToData(UserWithdrawBankInformationModel $model)
    {
        return [
            'id' => $model->id,
            'user_id' => $model->userId,
            'username' => $model->username,
            'bank_name' => $model->bankName,
            'bank_card_number' => $model->bankCardNumber,
            'pay_type' => $model->payType,
            'md5_hash' => $model->md5Hash,
            'default_hover' => $model->defaultHover,
            'create_time' => $model->createTime,
            'update_time' => $model->updateTime,
            'verify_status' => $model->verifyStatus,
            'verify_count' => $model->verifyCount,
        ];
    }

    /**
     * @param $data
     * @return UserWithdrawBankInformationModel
     */
    public function dataToModel($data)
    {
        $model = new UserWithdrawBankInformationModel();
        $model->id = $data['id'];
        $model->userId = $data['user_id'];
        $model->username = $data['username'];
        $model->bankName = $data['bank_name'];
        $model->bankCardNumber = $data['bank_card_number'];
        $model->payType = $data['pay_type'];
        $model->md5Hash = $data['md5_hash'];
        $model->defaultHover = $data['default_hover'];
        $model->createTime = $data['create_time'];
        $model->updateTime = $data['update_time'];
        $model->verifyStatus = $data['verify_status'];
        $model->verifyCount = $data['verify_count'];
        return $model;
    }

    /**
     * @param UserWithdrawBankInformationModel $model
     * @return int|string
     */
    public function storeModel(UserWithdrawBankInformationModel $model)
    {
//        过滤，一个用户创建超过5条则报错
        $count = $this->countUserIdForNumber($model->userId);
        if ($count >= 5) {
            throw new FQException("一个用户只能创建5条", 500);
        }
        $model->md5Hash = $this->makeMd5Hash($model);
        $data = $this->modelToData($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

    /**
     * @param UserWithdrawBankInformationModel $model
     * @return string
     */
    public function makeMd5Hash(UserWithdrawBankInformationModel $model)
    {
        $md5Str = sprintf("userId:%d_bankCardNumber:%s_payType:%d", $model->userId, $model->bankCardNumber, $model->payType);
        return md5($md5Str);
    }


    /**
     * @param UserWithdrawBankInformationModel $model
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function filterAccountId(UserWithdrawBankInformationModel $model)
    {
        $where[] = ['user_id', '=', $model->userId];
        $where[] = ['bank_card_number', '=', $model->bankCardNumber];
        $object = $this->getModel($this->shardingId)->where($where)->field("id")->find();
        if ($object === null) {
            return [];
        }
        return $object->toArray();
    }

    /**
     * @param $id
     * @return UserWithdrawBankInformationModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelWithLock($id)
    {
        $data = $this->getModel($this->shardingId)->lock(true)->where('id', $id)->find();
        if ($data === null) {
            return null;
        }
        return $this->dataToModel($data);
    }

    /**
     * @param $id
     * @return UserWithdrawBankInformationModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModel($id)
    {
        $data = $this->getModel($this->shardingId)->where('id', $id)->find();
        if ($data === null) {
            return null;
        }
        return $this->dataToModel($data);
    }


    /**
     * @param int $userId
     * @return int
     */
    private function countUserIdForNumber(int $userId)
    {
        return $this->getModel($this->shardingId)->where('user_id', $userId)->count("id");
    }


    /**
     * @param $userId
     * @param $deafultHover
     * @return bool
     */
    private function updateDefaultHaveForUserId($userId, $deafultHover)
    {
        return $this->getModel($this->shardingId)->where("user_id", $userId)->save(['default_hover' => $deafultHover]);
    }

    /**
     * @param $id
     * @param $deafultHover
     * @return bool
     */
    private function updateDefaultHaveForId($id, $deafultHover)
    {
        return $this->getModel($this->shardingId)->where("id", $id)->save(['default_hover' => $deafultHover]);
    }

    /**
     * @info 修改默认值
     * @param $userId
     * @param int $id
     * @param $setEmpty
     * @return bool
     * @throws FQException
     */
    public function changeDefaultForId($userId, int $id, $setEmpty)
    {
        if (empty($id)) {
            return false;
        }
        try {
            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($userId, $id, $setEmpty) {
                $model = $this->loadModelWithLock($id);
                if ($model === null) {
                    throw new FQException("操作失败数据异常", 500);
                }
                if ($model->userId !== $userId) {
                    throw new FQException("操作失败数据异常2", 500);
                }
                if ($setEmpty) {
                    $this->updateDefaultHaveForUserId($model->userId, UserWithdrawBankInformationHover::$NOT);
                } else {
                    $this->updateDefaultHaveForUserId($model->userId, UserWithdrawBankInformationHover::$NOT);
                    $this->updateDefaultHaveForId($model->id, UserWithdrawBankInformationHover::$YES);
                }
            });
            return true;
        } catch (FQException $e) {
            throw $e;
        }

    }

    /**
     * @return int|string
     */
    public function testStoreModel()
    {
        $unixTime = time();
        $model = new UserWithdrawBankInformationModel();
        $model->userId = 1439778;
        $model->bankPhone = "158010501255";
        $model->bankName = "李明";
        $model->bankCardNumber = "510401212321304120391023";
        $model->payType = UserWithdrawBankInformationPayType::$ZHIFUBAO;
        $model->defaultHover = 0;//默认选中 [0不是默认值,1是默认值]
        $model->createTime = $unixTime;
        $model->updateTime = 0;
        return $this->storeModel($model);
    }

    /**
     * @param $userId
     * @return UserWithdrawBankInformationModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelHoverForUserId($userId)
    {
        $model = new UserWithdrawBankInformationModel();
        if (empty($userId)) {
            return $model;
        }
        $where[] = ['user_id', "=", $userId];
        $where[] = ['default_hover', "=", 1];
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return $model;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }


    /**
     * @param $userId
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelListForUserId($userId)
    {
        $where[] = ['user_id', "=", $userId];
        $object = $this->getModel($this->shardingId)->where($where)->limit(5)->order("id", "desc")->select();
        if ($object == null) {
            return null;
        }

        $data = $object->toArray();
        $result = [];
        foreach ($data as $key => $itemData) {
            $itemModel = $this->dataToModel($itemData);
            $result[] = $itemModel;
        }
        return $result;
    }


    public function testChangeDefaultForId()
    {
        $id = 4;
        $userId = 1439778;
        return $this->changeDefaultForId($userId, $id, 0);
    }

    /**
     * @param $userId
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function deleteModel($userId, $id)
    {
        $where[] = ['id', '=', $id];
        $where[] = ['user_id', '=', $userId];
        return $this->getModel($this->shardingId)->where($where)->delete();
    }

    /**
     * @param $userId
     * @return bool|int
     * @throws FQException
     */
    public function deleteForUserId($userId){
        if (empty($userId)){
            return 0;
        }
        $where[] = ['user_id', '=', $userId];
        return $this->getModel($this->shardingId)->where($where)->delete();
    }

    /**
     * @param $id
     * @param $verifyStatus
     * @param int $step
     * @return \app\core\model\BaseModel
     * @throws FQException
     */
    public function incrVerifyCountForId($id, $verifyStatus, $step = 1)
    {
        $where['id'] = $id;
        $data['verify_status'] = $verifyStatus;
        return $this->getModel($this->shardingId)->where($where)->inc("verify_count", $step)->update($data);
    }

    /**
     * @param $id
     * @param $verifyStatus
     * @param int $step
     * @return \app\core\model\BaseModel
     * @throws FQException
     */
    public function updateStatusForId($id, $verifyStatus)
    {
        $where['id'] = $id;
        $data['verify_status'] = $verifyStatus;
        return $this->getModel($this->shardingId)->where($where)->update($data);
    }

}