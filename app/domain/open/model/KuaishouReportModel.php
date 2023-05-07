<?php

namespace app\domain\open\model;

/**
 * 快手上报数据模型
 */
class KuaishouReportModel
{
    public $id = 0;
    // 厂商类型 [juxing]
    public $factoryType = "";
    // 任务id，标识当前任务（总订单）
    public $missionId = "";
    // 订单id，标识当前任务下对应达人的子订单\n（此字段是唯一 且 可区分同一个任务下的多个达人）
    public $orderId = "";
    // iOS下的idfa计算MD5，规则为32位十六进制数字+4位连接符“-”的原文
    public $idfaMD5 = "";
    // 对15位数字的 IMEI （比如860576038225452）进行 MD5
    public $imeiMD5 = "";
    // 回调信息，编码一次的URL，长度小于10k
    public $callbackUrl = "";
    // 年月日
    public $strDate = "";
    // 创建时间
    public $createTime = 0;
}


