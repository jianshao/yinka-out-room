<?php


namespace app\service;


class OnlineUserService
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new OnlineUserService();
        }
        return self::$instance;
    }

    public function getOnlineUsers() {

    }
}