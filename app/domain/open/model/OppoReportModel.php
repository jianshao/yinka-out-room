<?php

namespace app\domain\open\model;

/**
 * oppo厂商report模型
 * $adid,$imeiMd5,$oaid,$timestamp,$androidId
 */
class OppoReportModel
{
    public $id = 0;
    // 厂商类型 PromoteFactoryTypeModel [juxing toutiao oppo]
    public $factoryType = "";
//    广告计划id
    public $adid = "";
//    安卓的设备 ID 的 md5 摘要，32位
    public $imeiMd5 = "";
//  android Q及更高版本的设备号，32位
    public $oaid = "";
//  安卓id原值的md5，32位
    public $androidId = "";
//    客户端发生广告点击事件的时间，
    public $tempstamp = "";
    // 年月日
    public $strDate = "";
    // 创建时间
    public $createTime = 0;
}


