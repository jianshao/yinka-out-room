<?php

namespace app\domain\user\model;


/**
 * @Info 举报用户模型
 * Class ComplaintUserModel
 * @package app\domain\user\model
 */
//状态：0待处理，1跟进中，2已完结
class ComplaintUserStatus
{
    public static $DAICHULI = 0;// 待处理

    public static $GENJINZHONG = 1;// 很进中

    public static $YIWANJIE = 2;// 已完结
}


