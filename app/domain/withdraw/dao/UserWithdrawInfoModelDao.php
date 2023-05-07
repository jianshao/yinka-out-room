<?php


namespace app\domain\withdraw\dao;


use app\core\mysql\ModelDao;
use app\domain\exceptions\FQException;
use app\domain\withdraw\model\UserWithdrawInfoModel;
use app\domain\withdraw\model\UserWithdrawInfoModelStauts;

//用户提现认证信息模型
class UserWithdrawInfoModelDao extends ModelDao
{
    protected $table = 'zb_user_withdraw_info';
    protected static $instance;
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $serviceName = 'commonMaster';
    protected $shardingId = 0;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserWithdrawInfoModelDao();
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
        try {
            $where['user_id'] = $model->userId;
            $where['status'] = UserWithdrawInfoModelStauts::$FAIL;
            $result = $this->updateModel($where, $model);
            if ($result >= 1) {
                return $result;
            }
            $data = $this->modelToData($model);
            return $this->getModel($this->shardingId)->insertGetId($data);
        } catch (\Exception $e) {
            if ($e->getCode() === 10501) {
                throw new FQException("添加失败不能重复添加了", 500);
            }
            return 0;
        }
    }

    /**
     * @param $userId
     * @param $status
     * @return UserWithdrawInfoModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModel($userId)
    {
        if (empty($userId)) {
            return null;
        }
        $where[] = ['user_id', '=', $userId];
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    private function updateModel($where, UserWithdrawInfoModel $model)
    {
        $data = $this->modelToData($model);
        unset($data["id"]);
        return $this->getModel($this->shardingId)->where($where)->update($data);
    }


    /**
     * @param $userId
     * @return UserWithdrawInfoModel
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadDataForSuccess($userId)
    {
        $model = new UserWithdrawInfoModel();
        if (empty($userId)) {
            return $model;
        }
        $where['user_id'] = $userId;
        $where['status'] = UserWithdrawInfoModelStauts::$SUCCESS;
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return $model;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }


    /**
     * @param $userId
     * @return UserWithdrawInfoModel
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadData($userId)
    {
        $model = new UserWithdrawInfoModel();
        if (empty($userId)) {
            return $model;
        }
        $where[] = ['user_id', '=', $userId];
        $where[] = ['status', '<>', UserWithdrawInfoModelStauts::$ClOSE];
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return $model;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @return int|string
     */
    public function testStoreModel()
    {
        $unixTime = time();
        $model = new UserWithdrawInfoModel();
        $model->userId = 1439778;
        $model->snsUserId = 1439778222;
        $model->identityCardFront = "www.kkkkfanqiepaidui.com";
        $model->identityCardOpposite = "www.ccccardopposite";
        $model->realPhone = "15810501263";
        $model->realName = "小明kkk";
        $model->identityNumber = "510401204120391023";
        $model->createTime = $unixTime;
        $model->updateTime = 0;
        $model->status = UserWithdrawInfoModelStauts::$AUDIT;
        return $this->getModel($this->shardingId)->storeModel($model);
    }


    /**
     * @param $id
     * @return bool|int
     * @throws FQException
     */
    public function delModelForId($id)
    {
        if (empty($id)) {
            return 0;
        }

        $where['id'] = $id;
        return $this->getModel($this->shardingId)->where($where)->delete();
    }

}