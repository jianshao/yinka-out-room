<?php


namespace constant;


class ApiRouteConstant
{
    /**
    *   c# 接口地址
    */
    const C_CURL_TIMEOUT = 3;
    //本地
//    const C_HOST = '192.168.1.100';
    //测试
    const C_HOST = '39.98.254.195';
    //指定用户砸出指定的礼物url
    const ADDGIFTTOUSER = '/Gift/AddGiftToUser';
    //取消指定用户砸出指定礼物url
    const DELETEGIFTTOUSER = '/Gift/DeleteGiftToUser';
}