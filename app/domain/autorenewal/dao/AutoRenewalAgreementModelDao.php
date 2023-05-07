<?php

namespace app\domain\autorenewal\dao;

use app\core\mysql\ModelDao;
use app\domain\autorenewal\model\AutoRenewalAgreementModel;

/**
 * @desc 支付宝签约表（自动续费）
 * Class AutoRenewalAgreementModelDao
 * @package app\domain\pay\dao
 */
class AutoRenewalAgreementModelDao extends ModelDao
{
    protected $serviceName = 'commonMaster';
    protected $table = 'zb_auto_renewal_agreement';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AutoRenewalAgreementModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data): AutoRenewalAgreementModel
    {
        $model = new AutoRenewalAgreementModel();
        $model->userId = $data['user_id'];
        $model->agreementNo = $data['agreement_no'];
        $model->externalAgreementNo = $data['external_agreement_no'];
        $model->transactionIds = $data['transaction_ids'];
        $model->status = $data['status'];
        $model->signTime = $data['sign_time'];
        $model->executeTime = $data['execute_time'];
        $model->signType = $data['sign_type'];
        $model->firstProductId = $data['first_product_id'];
        $model->productId = $data['product_id'];
        $model->renewStatus = $data['renew_status'];
        $model->outparam = $data['outparam'];
        $model->contractSource = $data['contract_source'];
        $model->configSource = $data['config_source'];
        return $model;
    }

    public function modelToData(AutoRenewalAgreementModel $model): array
    {
        return [
            'user_id' => $model->userId,
            'agreement_no' => $model->agreementNo,
            'external_agreement_no' => $model->externalAgreementNo,
            'transaction_ids' => $model->transactionIds,
            'status' => $model->status,
            'sign_time' => $model->signTime,
            'execute_time' => $model->executeTime,
            'sign_type' => $model->signType,
            'first_product_id' => $model->firstProductId,
            'product_id' => $model->productId,
            'renew_status' => $model->renewStatus,
            'outparam' => $model->outparam,
            'contract_source' => $model->contractSource,
            'config_source' => $model->configSource,
        ];
    }

    /**
     * @desc 插入数据
     * @param AutoRenewalAgreementModel $model
     * @return int|string
     */
    public function storeModel(AutoRenewalAgreementModel $model)
    {
        $data = $this->modelToData($model);

        return $this->getModel()->insertGetId($data);
    }


    /**
     * @desc 查询签约号
     * @param $agreementNo
     * @param $loadIsExpired // 是否查询过期的签约号
     * @return AutoRenewalAgreementModel
     */
    public function loadAgreement($agreementNo, $loadIsExpired = false)
    {
        $where[] = ['agreement_no', '=', $agreementNo];
        if (!$loadIsExpired){
            $where[] = ['status', '<>', 3];
        }
        $data = $this->getModel()->where($where)->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * @desc 查询用户签约成功的协议
     * @param $userId
     * @param $signType
     * @return AutoRenewalAgreementModel|null
     */
    public function getUserAgreement($userId, $signType)
    {
        $where = [];
        $where['user_id'] = $userId;
        $where['sign_type'] = $signType;
        $where['status'] = 1;
        $data = $this->getModel()->where($where)->find();
        if (!empty($data)) {
            return $this->dataToModel($data);
        }
        return null;
    }

    /**
     * @desc 修改协议状态
     * @param $agreementNo
     * @param $status
     */
    public function updateAgreementStatus($agreementNo, $status)
    {
        $where[] = ['agreement_no', '=', $agreementNo];
        $data['status'] = $status;

        return $this->getModel()->where($where)->update($data);
    }

    public function updateAgreement($agreementNo, $data)
    {
        $where[] = ['agreement_no', '=', $agreementNo];
        $where[] = ['status', '<>', 3];

        return $this->getModel()->where($where)->update($data);
    }

    /**
     * @desc 查询协议列表
     * @param $where
     * @param int $limit
     * @param $field
     * @return array
     */
    public function loadAgreementList($where, $limit = 10, $field = '*')
    {
        $data = $this->getModel()->field($field)->where($where)->limit($limit)->select();

        $result = [];
        foreach ($data as $item) {
            $result[] = $this->dataToModel($item);
        }
        return $result;
    }
}