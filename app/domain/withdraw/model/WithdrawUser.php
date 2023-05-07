<?php


namespace app\domain\withdraw\model;


use app\domain\asset\AssetItem;
use app\utils\ArrayUtil;

class WithdrawUser
{
    public static $NormalUser = 2;  // 普通用户
    public static $specialUser = 1; // 白名单用户

    public $userModel = null;
    public $userRole  = 0;



}