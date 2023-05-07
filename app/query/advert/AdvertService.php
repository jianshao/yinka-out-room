<?php


namespace app\query\advert;


use app\query\advert\AdvertModelDao;

class AdvertService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AdvertService();
        }
        return self::$instance;
    }

    public function getAdvertList() {
        return AdvertModelDao::getInstance()->getList();
    }
}