<?php


namespace app\domain\withdraw\dao;


use app\core\mysql\ModelDao;
use app\domain\exceptions\FQException;
use app\domain\withdraw\model\UserWithdrawDetailModel;
use app\domain\withdraw\model\UserWithdrawDetailOrderStatus;
use app\domain\withdraw\model\WithdrawUser;

class UserWithdrawDetailModelDao extends ModelDao
{
    protected $table = 'yyht_member_withdrawal';
    protected $serviceName = 'biMaster';
    protected static $instance;
    protected $pk = 'id';
    protected $shardingId = 0;


    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserWithdrawDetailModelDao();
        }
        return self::$instance;
    }

    public function modelToData(UserWithdrawDetailModel $model)
    {
        return [
            'id' => $model->id,
            'order_id' => $model->orderNumber,
            'uid' => $model->userId,
            'sns_order_number' => $model->snsOrderNumber,
            'diamond' => $model->diamond,
            'money' => $model->orderPrice,
            'bank_name' => $model->bankName,
            'username' => $model->username,
            'accounts' => $model->bankCardNumber,
            'type' => $model->payType,
            'user_role' => $model->userRole,
            'sns_agent_name' => $model->snsAgentName,
            'sns_agent_response' => $model->snsAgentResponse,
            'status' => $model->orderStatus,
            'message_detail' => $model->messageDetail,
            'created_time' => $model->createTime,
            'updated_time' => $model->updateTime,
            'callback_time' => $model->callbackTime,
            'date_str_month' => $model->dateStrMonth,
            'identity_number' => $model->identityNumber,
            'real_phone' => $model->realPhone,
        ];
    }

    /**
     * @param $data
     * @return UserWithdrawDetailModel
     */
    public function dataToModel($data)
    {
        $model = new UserWithdrawDetailModel();
        $model->id = $data['id'];
        $model->orderNumber = $data['order_id'];
        $model->userId = $data['uid'];
        $model->snsOrderNumber = $data['sns_order_number'];
        $model->diamond = $data['diamond'];
        $model->orderPrice = $data['money'];
        $model->bankName = $data['bank_name'];
        $model->username = $data['username'];
        $model->bankCardNumber = $data['accounts'];
        $model->payType = $data['type'];
        $model->userRole = $data['user_role'];
        $model->snsAgentName = $data['sns_agent_name'];
        $model->snsAgentResponse = $data['sns_agent_response'];
        $model->orderStatus = $data['status'];
        $model->messageDetail = $data['message_detail'];
        $model->createTime = $data['created_time'];
        $model->updateTime = $data['updated_time'];
        $model->callbackTime = $data['callback_time'];
        $model->dateStrMonth = $data['date_str_month'];
        $model->identityNumber = $data['identity_number'];
        $model->realPhone = $data['real_phone'];
        return $model;
    }

    /**
     * @param UserWithdrawDetailModel $model
     * @return int|string
     */
    public function storeModel(UserWithdrawDetailModel $model)
    {
        $data = $this->modelToData($model);
        return $this->getModel($this->shardingId)->insertGetId($data);
    }

    /**
     * @info 查询指定身份证用户 本月提现额度总值
     * 订单状态:0:待审核;1:打款中;2打款失败;3打款成功;4拒绝
     * @param $identityNumber
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function loadUserMonthAmountSum($identityNumber)
    {
        if (empty($identityNumber)) {
            return 0;
        }
        $strDate = date("Y-m");
        $balanceSetted = $this->balanceSetted($identityNumber, $strDate);
        $balanceAudit = $this->balanceAudit($identityNumber, $strDate);
        return $balanceSetted + $balanceAudit;
    }

    /**
     * @info 审核的
     * @param $identityNumber
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    private function balanceAudit($identityNumber, $strDate)
    {
        if (empty($identityNumber)) {
            return 0;
        }
        $where[] = ["identity_number", "=", $identityNumber];
        $where[] = ["date_str_month", "=", $strDate];
        $where[] = ["user_role", "=", WithdrawUser::$NormalUser];
        $where[] = ["status", "in", [UserWithdrawDetailOrderStatus::$AUDIT, UserWithdrawDetailOrderStatus::$PAYING]];
        $resultData = $this->getModel($this->shardingId)->where($where)->sum('money');
        return (int)$resultData;
    }

    /**
     * @info 已经提现的
     * @param $identityNumber
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    private function balanceSetted($identityNumber, $strDate)
    {
        if (empty($identityNumber)) {
            return 0;
        }
        $where[] = ["identity_number", "=", $identityNumber];
        $where[] = ["date_str_month", "=", $strDate];
        $where[] = ["ext_1", "=", "dalong"];
        $where[] = ["user_role", "=", WithdrawUser::$NormalUser];
        $where[] = ["status", "in", [UserWithdrawDetailOrderStatus::$SUCCESS]];
        $resultData = $this->getModel($this->shardingId)->where($where)->sum('money');
        return (int)$resultData;
    }


    /**
     * @param $userId
     * @param $strDate
     * @param $page
     * @param $pageNum
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModelForUserId($userId, $strDate, $page, $pageNum)
    {
        $where[] = ['uid', "=", $userId];
        $where[] = ['date_str_month', "=", $strDate];
        $offset = ($page - 1) * $pageNum;
        $object = $this->getModel($this->shardingId)->where($where)->order("id", "desc")->limit($offset, $pageNum)->select();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        $result = [];
        foreach ($data as $itemData) {
            $result[] = $this->dataToModel($itemData);
        }
        return $result;
    }


    /**
     * @param $userId
     * @param $strDate
     * @return float|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadOrderTotalPrice($userId, $strDate)
    {
        if (empty($userId) || empty($strDate)) {
            return 0;
        }
        $where[] = ['uid', "=", $userId];
        $where[] = ['date_str_month', "=", $strDate];
        $where[] = ['status', "=", UserWithdrawDetailOrderStatus::$SUCCESS];
        $object = $this->getModel($this->shardingId)->where($where)->order("id", "desc")->field("id")->select();
        $orderIds = $object->toArray();
        if (empty($orderIds)) {
            return 0;
        }

        $ids = array_column($orderIds, "id");
        $whereSecond[] = ['id', "in", $ids];
        return $this->getModel($this->shardingId)->where($whereSecond)->sum("money");
    }

    /**
     * @param $startTimeUnix
     * @param $limit
     * @return \Generator|null
     * @throws \app\domain\exceptions\FQException
     */
    public function getSyncOldDataGenerator($startTimeUnix, $limit)
    {
        $where[] = ['created_time', '>', $startTimeUnix];
        $where[] = ['date_str_month', '=', ''];
        $tempArr = $this->getModel($this->shardingId)->where($where)->limit($limit)->field("id")->column("id");
        if (empty($tempArr)) {
            return null;
        }
        foreach ($tempArr as $_ => $pkId) {
            yield $pkId;
        }
    }


    /**
     * @param $id
     * @return UserWithdrawDetailModel|null
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadModel($id)
    {
        if (empty($id)) {
            return null;
        }
        $where['id'] = $id;
        $object = $this->getModel($this->shardingId)->where($where)->find();
        if ($object === null) {
            return null;
        }
        $data = $object->toArray();
        return $this->dataToModel($data);
    }

    /**
     * @param UserWithdrawDetailModel $userWithdrawDetailModel
     * @return \app\core\model\BaseModel|int
     * @throws \app\domain\exceptions\FQException
     */
    public function updateStrTimeForModel(UserWithdrawDetailModel $userWithdrawDetailModel)
    {
        if ($userWithdrawDetailModel->dateStrMonth === "" || $userWithdrawDetailModel->id === 0) {
            return 0;
        }
        $data['date_str_month'] = $userWithdrawDetailModel->dateStrMonth;
        $where['id'] = $userWithdrawDetailModel->id;
        return $this->getModel($this->shardingId)->where($where)->update($data);
    }

    /**
     * @param $userId
     * @return int
     * @throws \app\domain\exceptions\FQException
     */
    public function existsAuditOrderForUserId($userId)
    {
        if (empty($userId)) {
            throw new FQException("用户信息异常", 500);
        }
        $where[] = ['uid', '=', $userId];
        $where[] = ['status', 'in', [UserWithdrawDetailOrderStatus::$AUDIT, UserWithdrawDetailOrderStatus::$PAYING]];
        $itemArr = $this->getModel($this->shardingId)->where($where)->limit(1)->column("id");
        if (empty($itemArr)) {
            return 0;
        }
        return (int)current($itemArr);
    }
}