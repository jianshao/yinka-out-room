<?php

namespace app\domain\withdraw\model;

class UserWithdrawDetailModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $orderNumber;

    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $snsOrderNumber;

    /**
     * @var string
     */
    public $orderPrice;

    /**
     * @var int
     */
    public $diamond;

    /**
     * @var string
     */
    public $bankName;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $bankCardNumber;

    /**
     * @var int
     */
    public $payType;

    /**
     * @var int
     */
    public $userRole;

    /**
     * @var string
     */
    public $snsAgentName;

    /**
     * @var string
     */
    public $snsAgentResponse;

    /**
     * @info 订单状态（1:订单审核中，2:订单拒绝，3:订单成功，4:订单退单，5:订单结算）
     * @var int
     */
    public $orderStatus;

    /**
     * @var string
     */
    public $messageDetail;

    /**
     * @var int
     */
    public $createTime;

    /**
     * @var int
     */
    public $updateTime;

    /**
     * @var int
     */
    public $callbackTime;

    /**
     * @var string
     */
    public $dateStrMonth;

    /**
     * @var string
     */
    public $identityNumber;

    /**
     * @var string
     */
    public $realPhone;
}

