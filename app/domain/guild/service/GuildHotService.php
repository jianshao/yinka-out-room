<?php


namespace app\domain\guild\service;

use app\common\RedisCommon;
use app\domain\room\dao\RoomModelDao;

class GuildHotService
{
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GuildHotService();
        }
        return self::$instance;
    }

    public function initRoomHotData(){

    }
}