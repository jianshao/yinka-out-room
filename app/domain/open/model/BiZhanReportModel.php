<?php

namespace app\domain\open\model;

/**
 * b站上报模型
 */
class BiZhanReportModel
{
    public $id = 0;
    // 厂商类型 PromoteFactoryTypeModel BiZhan
    public $factoryType = "";
//    追踪id
    public $trackId = "";
//    账户ID
    public $accountId = "";
//    计划 ID
    public $campaignId = "";
//    单元 ID
    public $unitId = "";
//    创意 ID，
    public $creativeId = "";
//    客户端操作系统 数字,取 0~3。0 表示 Android，1表示 iOS，2 表示 Windows Phone，3 表示其他
    public $os = "";
//    用户终端的 IMEI 原始值为 15 位 IMEI，取其 32 位小写 MD5 编码
    public $imei = "";
//    回调地址（需要 urlencode） 字符串，需 urlencode 编码，如https://cm.bilibili.com/conv/api/conversion/ad/cb/v1?track_id=__track_id__ ,（回传链接中 track_id 会替换成对应值）
    public $callbackUrl = "";
//    MAC地址md5
    public $mac1 = "";
//    iOS IDFA
    public $idfaMd5 = "";
//    Android AdvertisingID
    public $aaId = "";
//    用户终端的 Android ID
    public $androidId = "";
//    安卓匿名设备标识符
    public $oaidMd5 = "";
//    客户端触发监测的时间  UTC 时间戳毫秒数
    public $ts = 0;
    // 年月日
    public $strDate = "";
    // 创建时间
    public $createTime = 0;
}


