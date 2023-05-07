<?php

namespace app\domain\withdraw\model;

//用户提现认证信息
class UserWithdrawInfoModel
{
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $userId = 0;

    /**
     * @var string
     */
    public $snsUserId = '';

    /**
     * @var string
     */
    public $identityCardFront = '';

    /**
     * @var string
     */
    public $identityCardOpposite = '';

    /**
     * @var string
     */
    public $realPhone = '';

    /**
     * @var string
     */
    public $realName = '';

    /**
     * @var string
     */
    public $identityNumber = '';

    /**
     * @var int
     */
    public $status = 0;

    /**
     * @var int
     */
    public $createTime = 0;

    /**
     * @var int
     */
    public $updateTime = 0;

    /**
     * @var string
     */
    public $messageDetail = "";


}

