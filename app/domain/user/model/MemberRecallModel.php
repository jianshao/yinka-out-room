<?php


namespace app\domain\user\model;


/**
 * @info 用户召回(长期)模型
 * Class MemberRecallModel
 * @package app\domain\user\model
 */
class MemberRecallModel
{
    public $id = 0;
    public $userId = 0;
    public $originLoginTime = 0;
    public $chargeStatus = 0;
    public $amount = 0;
    public $freeCoin = 0;
    public $coinBalance = 0;
    public $snsResponse = "";
    public $loginTime  =0;
    public $recallLoginStatus = 0;
    public $mobile="";
    public $type="";
    public $pushWhenTime=0;
    public $snsId="";
    public $snsConfirm=0;
    public $strDate="";
    public $createTime=0;
    public $updateTime=0;
}


