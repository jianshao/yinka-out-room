<?php

namespace app\domain\withdraw\model;

class UserWithdrawBankInformationModel
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
    public $username = '';

    /**
     * @var string
     */
    public $bankName = '';

    /**
     * @var string
     */
    public $bankCardNumber = '';

    /**
     * @var int
     */
    public $payType = 0;


    public $md5Hash = '';

    /**
     * 默认选中 [0不是默认值,1是默认值]
     * @var int
     */
    public $defaultHover = 0;

    /**
     * @var int
     */
    public $createTime = 0;

    /**
     * @var int
     */
    public $updateTime = 0;

    /**
     * @var int
     */
    public $verifyStatus = 0;

    /**
     * @var int
     */
    public $verifyCount = 0;
}

