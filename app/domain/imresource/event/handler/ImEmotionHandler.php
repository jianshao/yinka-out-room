<?php


namespace app\domain\imresource\event\handler;


use app\domain\imresource\service\ImEmotionService;
use think\facade\Log;

class ImEmotionHandler
{
    /**
     * @desc svip过期，清理特权
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            Log::info(sprintf('ImEmotionHandler::onVipExpiresEvent userId=%d vipLevel=%s ',
                $event->userId, $event->vipLevel));
            // 清理IM气泡、IM背景、IM表情包、特别关注
            if ($event->vipLevel == 2) {
                ImEmotionService::getInstance()->setImEmotion($event->userId, 'remove_all');
            }
        } catch (Exception $e) {
            Log::error(sprintf('ImEmotionHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}