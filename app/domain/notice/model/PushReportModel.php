<?php

namespace app\domain\notice\model;

//第三方上报模型
class PushReportModel
{
    // 主键
    public $id = 0;
    // 平台信息
    public $platform = "";
    // 接收验证的用户名
    public $receiver = "";
    // 接收验证的密码，
    public $pswd = "";
    // 消息id
    public $msgId = "";
//    任务id
    public $taskId = "";
    // 运营商返回的状态更新时间
    public $reportTime = "";
    // 接收短信的手机号码
    public $mobile = "";
    // 运营商返回的状态
    public $status = "";
    // 253平台收到运营商回复状态报告的时间
    public $notifyTime = "";
    // 状态说明，内容经过URLEncode编码(UTF-8)
    public $statusDesc = "";
    // 该条短信在您业务系统内的ID
    public $uid = "";
    // 下发短信计费条数
    public $length = 0;
    // 原始数据
    public $originParam = "";
//    拓展字段
    public $ext_1 = "";

    //创建时间
    public $createTime = 0;
}


