<?php

namespace app\domain\promote\event\handler;

use app\common\RedisCommon;
use app\domain\pay\dao\OrderModelDao;
use app\domain\pay\ProductSystem;
use app\domain\promote\CauseService;
use app\domain\promote\PromoteUtilService;
use app\event\AndroidActivateEvent;
use app\event\ChargeEvent;
use app\event\UserLoginEvent;
use app\event\UserRegisterEvent;
use app\form\ClientInfo;
use Exception;
use think\facade\Log;

// 推广handler
class PromoteHandler
{


    /**
     * @Info 安卓注册
     * @param AndroidActivateEvent $event
     * @throws \app\domain\exceptions\FQException
     */
    public function onAndroidActivateEvent(AndroidActivateEvent $event)
    {
        try {
            $channleType = $event->clientInfo->channel;
            $CauseService = new CauseService($channleType);
            $result = $CauseService->report($event);
            Log::info(sprintf('PromoteHandler::onAndroidActivateEvent info channleType=%s toutiaoReportRe=%d', $channleType, $result));
        } catch (Exception $e) {
            Log::warning(sprintf('PromoteHandler::onAndroidActivateEvent channleType=%d ex=%d:%s', $channleType, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 用户注册行为
     * @param UserRegisterEvent $event
     * @throws \app\domain\exceptions\FQException
     */
    public function onUserRegisterEvent(UserRegisterEvent $event)
    {
        try {
            $channleType = PromoteUtilService::getInstance()->loadChannleForUserId($event->userId);
            $CauseService = new CauseService($channleType);
            $result = $CauseService->report($event);
            Log::info(sprintf('PromoteHandler::onUserRegisterEvent info userId=%d channleType=%s toutiaoReportRe=%d', $event->userId, $channleType, $result));
        } catch (Exception $e) {
            Log::warning(sprintf('PromoteHandler::onUserRegisterEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @info 推广归因 次留
     * @param UserLoginEvent $event
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
            $channleType = PromoteUtilService::getInstance()->loadChannleForUserId($event->userId);
            $CauseService = new CauseService($channleType);
            $result = $CauseService->report($event);
            Log::info(sprintf('PromoteHandler::onUserLoginEvent info userId=%d channleType=%s toutiaoReportRe=%d', $event->userId, $channleType, $result));
        } catch (Exception $e) {
            Log::error(sprintf('PromoteHandler::onUserLoginEvent MemberRecallService userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 推广归因 充值
     * @param ChargeEvent $event
     */
    public function onChargeEvent(ChargeEvent $event)
    {
        try {
            $redis = RedisCommon::getInstance()->getRedis();
            $firstPayNotice = $redis->hget(sprintf('userinfo_%s', $event->userId), 'first_pay_notice');
            if (empty((int)$firstPayNotice)) {
                $channleType = PromoteUtilService::getInstance()->loadChannleForUserId($event->userId);
                $CauseService = new CauseService($channleType);
                $result = $CauseService->report($event);
                Log::info(sprintf('PromoteHandler::onChargeEvent info userId=%d channleType=%s toutiaoReportRe=%d', $event->userId, $channleType, $result));
            }
        } catch (Exception $e) {
            Log::error(sprintf('PromoteHandler::onChargeEvent MemberRecallService userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @info 测试
     */
    public function testChargeEvent()
    {
        $timestamp = time();
        $dealId = "2021060322001423621417372345";
        $order = OrderModelDao::getInstance()->findOrderByDealId($dealId);
        $product = ProductSystem::getInstance()->findProduct($order->productId);
        $order->userId = 1700958;
        $eventModel = new ChargeEvent($order, $product, $timestamp);
        return $this->onChargeEvent($eventModel);
    }

    public function testUserLoginEvent($request)
    {
        $userId = 2394510;
        $lastLoginTime = '2021-12-28 11:15:25';
        $clientInfo = new ClientInfo();
        $clientInfo->fromRequest($request);
        $eventObject = new UserLoginEvent($userId, $lastLoginTime, time(), $clientInfo);
        return $this->onUserLoginEvent($eventObject);
    }


}