<?php


namespace app\domain\specialcare\event\handler;


use app\domain\specialcare\service\UserSpecialCareService;
use app\event\UserLoginEvent;
use think\facade\Log;

/**
 * @desc 特别关心
 * Class SpecialCareHandler
 * @package app\domain\specialcare\event\handler
 */
class SpecialCareHandler
{
    /**
     * @desc svip过期，将特别关心清除
     * @param $event
     */
    public function onVipExpiresEvent($event)
    {
        try {
            Log::info(sprintf('SpecialCareEventHandler::onVipExpiresEvent userId=%d vipLevel=%s ',
                $event->userId, $event->vipLevel));
            // 清理IM气泡、IM背景、IM表情包、特别关注
            if ($event->vipLevel == 2) {
                UserSpecialCareService::getInstance()->cancelSpecialCare($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('SpecialCareEventHandler::onVipExpiresEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @desc 发送到消息队列，通知特别关注的人
     * @param UserLoginEvent $event
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
            Log::info(sprintf('SpecialCareEventHandler::onUserLoginEvent userId=%d timestamp=%d', $event->userId,
                $event->timestamp));
            // 发送消息队列
            UserSpecialCareService::getInstance()->createMessageQueue($event->userId, 1, $event->timestamp);
        } catch (Exception $e) {
            Log::error(sprintf('SpecialCareEventHandler::onUserLoginEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @desc 动态发布成功
     * @param $event
     * @throws \Exception
     */
    public function onForumCheckPassEvent($event)
    {
        try {
            Log::info(sprintf('SpecialCareEventHandler::onForumCheckPassEvent userId=%d timestamp=%d', $event->userId,
                $event->timestamp));
            // 发送消息队列
            UserSpecialCareService::getInstance()->createMessageQueue($event->userId, 2, $event->timestamp);
        } catch (Exception $e) {
            Log::error(sprintf('SpecialCareEventHandler::onForumCheckPassEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @desc 关注事件
     * @param $event
     */
    public function onFocusFriendDomainEvent($event) {
        try {
            $userId = $event->user->getUserId();
            Log::info(sprintf('SpecialCareEventHandler::onFocusFriendDomainEvent userId=%d friendId=%d  isFocus=%s ',
                $userId, $event->friendId,$event->isFocus));
            // 取关
            if ($event->isFocus == 2){
                UserSpecialCareService::getInstance()->setSpecialCare($userId, $event->friendId, 2);
            }
        } catch (Exception $e) {
            Log::error(sprintf('SpecialCareEventHandler::onFocusFriendDomainEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->user->getUserId(), $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}