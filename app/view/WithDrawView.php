<?php

namespace app\view;

use app\domain\models\UserIdentityModel;
use app\domain\user\model\UserModel;
use app\domain\user\service\UserService;
use app\domain\withdraw\model\UserWithdrawBankInformationModel;
use app\domain\withdraw\model\UserWithdrawDetailModel;
use app\domain\withdraw\model\UserWithdrawInfoModel;
use app\utils\CommonUtil;

class WithDrawView
{

    /**
     * @param UserWithdrawDetailModel $model
     * @return array
     */
    public static function viewWithDrawOrder(UserWithdrawDetailModel $model)
    {
        $bankCardNumber = CommonUtil::filterCardAndMail($model->bankCardNumber);
        return [
            'orderNumber' => $model->orderNumber,
            'orderPrice' => $model->orderPrice,
            'bankCardNumber' => $bankCardNumber,
            'username' => $model->username,
            'bankName' => $model->bankName,
            'payType' => $model->payType,
            'orderStatus' => $model->orderStatus,
            'messageDetail' => $model->messageDetail,
            'createTime' => $model->createTime,
        ];
    }


    /**
     * @param UserWithdrawBankInformationModel $model
     * @return array
     */
    public static function withDrawBankShowView(UserWithdrawBankInformationModel $model)
    {
        return [
            'id' => $model->id,
            'bankName' => $model->bankName,
            'bankCardNumber' => $model->bankCardNumber,
            'payType' => $model->payType,
            'defaultHover' => $model->defaultHover,
        ];
    }

    /**
     * @param UserModel $userModel
     * @return array
     */
    public static function viewUserInfo(UserModel $userModel, $token = "")
    {
        $ret = [
            'userid' => $userModel->userId,
            'guildId' => $userModel->guildId,
            'nickname' => $userModel->nickname,
            'token' => $token,
            'sex' => $userModel->sex,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'attestation' => (int)$userModel->attestation,
        ];
        //获取用户状态
        $ret['accountState'] = UserService::getInstance()->getUserStatus($userModel);
        return $ret;
    }


    /**
     * @param $diamond
     * @param $banlance
     * @return array
     */
    public static function viewUserAssets($diamond, $banlance)
    {
        $ret = [
            'diamond' => $diamond,
            'banlance' => $banlance,
        ];
        return $ret;
    }

    /**
     * @param UserIdentityModel $model
     * @return array
     */
    public static function identityInfoView(UserIdentityModel $model)
    {
        return [
            'userId' => $model->userId,
            'certName' => $model->certName,
            'certNo' => $model->certno,
        ];
    }


    /**
     * @param UserWithdrawInfoModel $model
     * @return array
     */
    public static function viewWithDrawInfo(UserWithdrawInfoModel $model)
    {
        return [
            'status' => $model->status,
            'messageDetail' => $model->messageDetail,
        ];
    }


    /**
     * @param UserWithdrawBankInformationModel $model
     * @return array
     */
    public static function viewWithDrawBank(UserWithdrawBankInformationModel $model)
    {
        return [
            'id' => $model->id,
            'name' => $model->bankName,
            'cardNumber' => $model->bankCardNumber,
            'payType' => $model->payType,
        ];
    }


}