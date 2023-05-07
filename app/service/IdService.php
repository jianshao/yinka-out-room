<?php

namespace app\service;


use app\core\mysql\Sharding;
use app\domain\dao\IdDao;
use think\facade\Log;

class IdService
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new IdService();
        }
        return self::$instance;
    }

    public function getNextUserId() {
        try {
            return Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function() {
                $ret = IdDao::getInstance()->getNextId(IdTypes::$USER);
                Log::info(sprintf('IdService::getNextUserId ret=%d', $ret));
                return $ret;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getNextRoomId() {
        try {
            return Sharding::getInstance()->getConnectModel('commonMaster',0)->transaction(function() {
                $ret = IdDao::getInstance()->getNextId(IdTypes::$ROOM);
                Log::info(sprintf('IdService::getNextRoomId ret=%d', $ret));
                return $ret;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}