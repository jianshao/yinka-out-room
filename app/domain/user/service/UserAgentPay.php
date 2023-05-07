<?php

namespace app\domain\user\service;


//用户第三方支付
class UserAgentPay
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new VisitorService();
        }
        return self::$instance;
    }


}