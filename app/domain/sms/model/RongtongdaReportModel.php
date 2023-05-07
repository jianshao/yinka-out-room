<?php


namespace app\domain\sms\model;


//短信运营商上报数据
//uid=839&uname=642799&seq=202202151124484514&pn=18515985536&stm=20220215102736&sc=DELIVRD&st=20220215102746&bid=202202151124484513&pid=1
class RongtongdaReportModel
{
//    用户编号
    public $uid = 0;
//    用户名称
    public $uname = "";
//    发送编号
    public $seq = 0;
//    pn目标手机号
    public $pn = 0;
//    发送时间
    public $stm = "";
//    状态码
    public $sc = "";
//    状态时间
    public $st = "";
//    批次号
    public $bid = "";
//    时间日期格式
    public $str_date = 0;
//    平台
    public $platform = "";
//    创建时间
    public $create_time = 0;
//    原始数据
    public $origin_data = "";
}