<?php


namespace app\domain\user\event\handler;


use app\domain\user\service\AttentionService;
use think\facade\Log;

/**
 * @desc 用户备注
 * Class UserRemarkNameHandler
 * @package app\domain\user\event\handler
 */
class UserRemarkNameHandler
{
    /**
     * @desc 取消关注同时取消用户备注
     * @param $event
     */
    public function onFocusFriendDomainEvent($event)
    {
        try {
            $userId = $event->user->getUserId();
            Log::info(sprintf('UserRemarkNameHandler::onFocusFriendDomainEvent userId=%d friendId=%d  isFocus=%s ',
                $userId, $event->friendId, $event->isFocus));
            // 取关
            if ($event->isFocus == 2) {
                AttentionService::getInstance()->setUserRemark($userId, $event->friendId, '');
            }
        } catch (Exception $e) {
            Log::error(sprintf('UserRemarkNameHandler::onFocusFriendDomainEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->user->getUserId(), $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}