<?php


namespace app\domain\imresource\event\handler;


use app\domain\imresource\service\ImBackgroundService;
use think\facade\Log;

/**
 * @desc im背景
 * Class ImBackgroundHandler
 * @package app\domain\imresource\event\handler
 */
class ImBackgroundHandler
{
    /**
     * @desc svip过期，清理特权
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            Log::info(sprintf('ImBackgroundHandler::onVipExpiresEvent userId=%d vipLevel=%s ',
                $event->userId, $event->vipLevel));
            // 清理IM气泡、IM背景、IM表情包、特别关注
            if ($event->vipLevel == 2) {
                ImBackgroundService::getInstance()->delImBackground($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('ImBackgroundHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}