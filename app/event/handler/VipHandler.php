<?php


namespace app\event\handler;


use app\domain\vip\service\VipService;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class VipHandler
{
    // 用户登录，处理爵位信息
    public function onUserLoginEvent($event) {
        try {
            Log::info(sprintf('VipHandler::onUserLoginEvent userId=%d lastLoginTime=%s',
                $event->userId, TimeUtil::timeToStr($event->lastLoginTime)));
            VipService::getInstance()->processVipWhenUserLogin($event->userId, $event->lastLoginTime, $event->timestamp);
        } catch (Exception $e) {
            Log::error(sprintf('VipHandler::onUserLoginEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @desc 购买成功处理信息
     * @param $event
     */
    public function onBuyVipEvent($event)
    {
        try {
            Log::info(sprintf('VipHandler::onBuyVipEvent userId=%d orderId=%s vipLevel=%s count=%s',
                $event->userId, $event->orderId , $event->vipLevel, $event->count));

            VipService::getInstance()->cacheVipPayInfo($event->userId);
        } catch (Exception $e) {
            Log::error(sprintf('VipHandler::onBuyVipEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}