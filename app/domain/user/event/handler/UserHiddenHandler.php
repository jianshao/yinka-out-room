<?php


namespace app\domain\user\event\handler;


use app\domain\user\service\UserInfoService;
use app\domain\user\service\VisitorService;
use think\facade\Log;

/**
 * @desc 用户隐身相关
 * Class UserHiddenHandler
 * @package app\domain\user\event\handler
 */
class UserHiddenHandler
{
    /**
     * @desc svip过期，清理特权
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            Log::info(sprintf('UserHiddenHandler::onVipExpiresEvent userId=%d vipLevel=%s ',
                $event->userId, $event->vipLevel));
            if ($event->vipLevel == 2) {
                // 取消在线状态
                UserInfoService::getInstance()->setHiddenOnline($event->userId, 2);
                // 取消用户的所有隐身访问
                VisitorService::getInstance()->cancelHiddenVisitor($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('UserHiddenHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}