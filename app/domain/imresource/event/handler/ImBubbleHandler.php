<?php


namespace app\domain\imresource\event\handler;


use app\domain\imresource\service\ImBubbleService;
use think\facade\Log;

class ImBubbleHandler
{
    /**
     * @desc svip过期，清理特权
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            Log::info(sprintf('ImBubbleHandler::onVipExpiresEvent userId=%d vipLevel=%s ',
                $event->userId, $event->vipLevel));
            // 清理IM气泡、IM背景、IM表情包、特别关注
            if ($event->vipLevel == 2) {
                ImBubbleService::getInstance()->setImBubble($event->userId, 0);
            }
        } catch (Exception $e) {
            Log::error(sprintf('ImBubbleHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}