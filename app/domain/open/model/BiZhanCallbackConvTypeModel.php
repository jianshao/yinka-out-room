<?php

namespace app\domain\open\model;


class BiZhanCallbackConvTypeModel
{

//    安卓用户进行一次 APP 下载成功的
    public static $APP_DOWNLOAD = "APP_DOWNLOAD";
//    安卓用户进行一次 APP 安装成功的行为
    public static $APP_INSTALL = "APP_INSTALL";
//    新用户下载应用后首次打开应用   (激活)
    public static $APP_FIRST_ACTIVE = "APP_FIRST_ACTIVE";
//    落地页上发生表单提交行为
    public static $FORM_SUBMIT = "FORM_SUBMIT";
//    教育完成表单付费行为
    public static $FORM_USER_COST = "FORM_USER_COST";
//    应用内注册，下载 app 激活后在应用内完成注册      （注册）
    public static $USER_REGISTER = "USER_REGISTER";
//    在客户产品上完成一次订单提交
    public static $ORDER_PLACE = "ORDER_PLACE";
//    在应用内完成付费的行为        (付费)
    public static $USER_COST = "USER_COST";
//    下载 APP 激活后，次日依然打开过APP     (次留)
    public static $RETENTION = "RETENTION";
//    通过 B 站成功唤起一次 APP
    public static $APP_CALLUP = "APP_CALLUP";
//    用户在 app 内完成您定义的有价值/有效行为(如浏览)计为一次有效行为
    public static $ACTION_VALID = "ACTION_VALID";
//    在客户产品上完成的第一次购买行为或订单提交计为首次购买
    public static $FIRST_ORDER_PLACE = "FIRST_ORDER_PLACE";

    private static function getCoveMap()
    {
        return [
            self::$APP_FIRST_ACTIVE => 1,
            self::$USER_REGISTER => 2,
            self::$USER_COST => 3,
            self::$RETENTION => 4,
        ];
    }

    /**
     * @param $typeName
     * @return int
     */
    public static function getEvnetTypeForTypeName($typeName)
    {
        $result = 99;
        $eventTypeMap = self::getCoveMap();
        if (isset($eventTypeMap[$typeName])) {
            $result = $eventTypeMap[$typeName];
        }
        return $result;
    }
}

