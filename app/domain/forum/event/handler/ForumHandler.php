<?php


namespace app\domain\forum\event\handler;


use app\domain\forum\service\ForumService;
use think\facade\Log;

/**
 * @desc 动态
 * Class ForumHandler
 * @package app\domain\imresource\event\handler
 */
class ForumHandler
{
    /**
     * @desc svip过期，清理特权
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            Log::info(sprintf('ForumHandler::onVipExpiresEvent userId=%d vipLevel=%s ',
                $event->userId, $event->vipLevel));
            // 清理动态置顶
            if ($event->vipLevel == 2) {
                $time = time();
                ForumService::getInstance()->cancelUserForumTop($event->userId, $time);
            }
        } catch (Exception $e) {
            Log::error(sprintf('ForumHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}