<?php

namespace app\domain\models;

class LoginDetailNewModel extends LoginDetailModel
{
    public $version = "";  //版本
    public $simulator = false; //是否为模拟器
    public $imei = "";  // 设备号
    public $appId = "";  //包名
    public $source = ""; //渠道名
    public $ext_param_1 = ""; //是否注册 1注册 0登录
    public $ext_param_2 = ""; //扩展参数字段2
    public $ext_param_3 = ""; //扩展参数字段3
    public $ext_param_4 = ""; //扩展参数字段4
    public $ext_param_5 = ""; //扩展参数字段5

}