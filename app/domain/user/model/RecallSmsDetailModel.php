<?php


namespace app\domain\user\model;


/**
 * @info 短信召回活动的用户数据详情模型
 * Class RecallSmsDetailModel
 * @package app\domain\user\model
 */
class RecallSmsDetailModel
{
    public $id = 0;
    public $userId = 0;
    public $sendGift=0;
    public $originLoginTime = 0;
    public $smsStatus = 0;
    public $smsDetail = "";
    public $loginTime = 0;
    public $createTime = 0;
    public $updateTime = 0;
}


