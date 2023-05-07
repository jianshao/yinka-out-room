<?php

namespace app\service;


use app\core\mysql\Sharding;
use app\domain\dao\IdDao;
use app\domain\dao\IdTestDao;
use think\facade\Log;

class IdTestService
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new IdTestService();
        }
        return self::$instance;
    }

    public function getNextUserId() {
        try {
            return Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function() {
                $ret = IdTestDao::getInstance()->getNextId(IdTypes::$USER);
                Log::info(sprintf('IdService::getNextUserId ret=%d', $ret));
                return $ret;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}