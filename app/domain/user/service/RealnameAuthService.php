<?php


namespace app\domain\user\service;


use app\utils\CommonUtil;

class RealnameAuthService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RealnameAuthService();
        }
        return self::$instance;
    }

    public function realnameAuth($userId, $idCard, $name) {
        CommonUtil::validateIdCard($idCard);

    }
}