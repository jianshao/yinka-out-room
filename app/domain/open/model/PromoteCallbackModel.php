<?php

namespace app\domain\open\model;

/**
 * 头条的推广回调模型
 */
class PromoteCallbackModel
{
    public $id = 0;

    public $userId = 0;
    // 厂商类型 [juxing]
    public $factoryType = "";
    // iOS下的idfa计算MD5，规则为32位十六进制数字+4位连接符“-”的原文
    public $idfaMD5 = "";
    // 对15位数字的 IMEI （比如860576038225452）进行 MD5
    public $imeiMD5 = "";
//    oaid
    public $oaid = "";
    // 回调信息，编码一次的URL，长度小于10k
    public $callbackUrl = "";

    public $status = 0;

    public $eventType=0;

    public $response = "";
    // 年月日
    public $strDate = "";
    // 创建时间
    public $createTime = 0;
}


