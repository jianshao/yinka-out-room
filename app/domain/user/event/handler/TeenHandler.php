<?php

namespace app\domain\user\event\handler;

use app\domain\user\event\UserLoginDomainEvent;
use app\domain\user\service\MonitoringService;
use app\event\UserLoginEvent;
use think\facade\Log;
use Exception;

// 青少年模式handler
class TeenHandler
{
    /**
     * @info  启动青少年模式计时器
     * @param UserLoginDomainEvent $event
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
            $re = MonitoringService::getInstance()->runMonitorImpl($event->userId);
            Log::info(sprintf('TeenHandler::onUserLoginEvent success userId=%d result=%d',
                $event->userId, $re));
        } catch (Exception $e) {
            Log::warning(sprintf('TeenHandler::onUserLoginEvent error userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

}