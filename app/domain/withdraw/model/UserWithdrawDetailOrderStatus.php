<?php

namespace app\domain\withdraw\model;

class UserWithdrawDetailOrderStatus
{

//0:待审核;1:打款中;2打款失败;3打款成功;4拒绝
    /**
     * @var int
     */
    public static $AUDIT = 0;

    /**
     * @var int
     */
    public static $PAYING = 1;

    /**
     * @var int
     */
    public static $ERROR = 2;

    /**
     * @var int
     */
    public static $SUCCESS = 3;

    /**
     * @var int
     */
    public static $REJECT = 4;

}

