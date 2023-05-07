<?php

namespace app\domain\withdraw\model;

class UserWithdrawInfoModelStauts
{

    public static $AUDIT=0;

    /**
     * @var int
     */
    public static $SUCCESS = 1;

    /**
     * @var int
     */
    public static $FAIL = 2;

    /**
     * @info 已废弃
     * @var int
     */
    public static $ClOSE = 3;
}

