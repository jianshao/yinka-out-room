<?php

namespace app\domain\open\model;

/**
 * 头条上报数据模型
 * $aid, $cid, $idfa, $imei, $mac,$oaid,$androidid,$os,$tempstamp,$callback
 */
class ToutiaoReportModel
{
    public $id = 0;
    // 厂商类型 PromoteFactoryTypeModel [juxing toutiao]
    public $factoryType = "";
//    广告计划id
    public $aid = "";
//    广告创意 id，长整型
    public $cid = "";
//    OS 6+的设备id字段，32位
    public $idfa = "";
//    安卓的设备 ID 的 md5 摘要，32位
    public $imei = "";
//    移动设备mac地址,转换成大写字母,去掉“:”，并且取md5摘要后的结果
    public $mac = "";
//  android Q及更高版本的设备号，32位
    public $oaid = "";
//  安卓id原值的md5，32位
    public $androidid = "";
//    操作系统平台  安卓：0 ,IOS：1 ,其他：3
    public $os = 0;
//    客户端发生广告点击事件的时间，以毫秒为单位时间戳
    public $tempstamp = "";
//    一些跟广告信息相关的回调参数，内容是一个加密字符串，在调用事件回传接口的时候会用到
    public $callback = "";
    // 年月日
    public $strDate = "";
    // 创建时间
    public $createTime = 0;
//    参数1 DEMAND_ID  计划ID，一次营销活动生成一个计划ID
    public $ext1 = '';
//    参数2 ITEM_ID  视频ID
    public $ext2 = '';
}


