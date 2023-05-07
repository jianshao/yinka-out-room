<?php

namespace app\domain\activity\event\handler;


use app\domain\recall\service\MemberRecallService;
use app\event\UserLoginEvent;
use Exception;
use think\facade\Log;

class RecallDomainHandler
{
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        Log::info(sprintf('RecallDomainHandler::onUserLoginEvent entry event:%s', json_encode($event)));
//        召回活动-短期
//        try {
////            RecallSmsService::getInstance()->recallReward($event);
//        } catch (Exception $e) {
//            Log::info(sprintf('RecallDomainHandler::onUserLoginEvent recallReward userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
//        }

//        召回活动-长期
        try {
            MemberRecallService::getInstance()->onUserLoginEvent($event);
        } catch (Exception $e) {
            Log::info(sprintf('RecallDomainHandler::onUserLoginEvent MemberRecallService userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


}