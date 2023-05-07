<?php


namespace app\domain\led\event\handler;


use app\domain\led\LedService;
use Exception;
use think\facade\Log;

class LedEventHandler
{

    // 矿石兑换
    public function onOreExchangeEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onOreExchangeEvent'));
            LedService::getInstance()->buildOreExchangeLedMsg($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onOreExchangeEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }


    public function onTaoJinRewardEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onTaoJinRewardEvent'));
            LedService::getInstance()->buildTaoJingLedMsg($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onTaoJinRewardEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function onSendGiftEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onSendGiftEvent'));
            LedService::getInstance()->buildSendGifLedMsg($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onSendGiftEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function onBreakBoxNewEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onBreakBoxNewEvent'));
            LedService::getInstance()->buildBoxLedMsg($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onBreakBoxNewEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function onTurntableEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onTurntableEvent'));
            LedService::getInstance()->buildTurnTableLedMsg($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onTurntableEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function onDukeLevelChangeEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onDukeLevelChangeEvent'));
            LedService::getInstance()->buildDukeLevelChangeEvent($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onDukeLevelChangeEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function onBuyVipEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onBuyVipEvent userId=%d vipLevel=%d', $event->userId, $event->vipLevel));
            LedService::getInstance()->buildBuyVipEvent($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onBuyVipEvent userId=%d vipLevel=%d ex=%d:%s',
                $event->userId, $event->vipLevel,
                $e->getCode(), $e->getMessage()));
        }
    }

    public function onSendRedPacketEvent($event) {
        try {
            Log::info(sprintf('LedEventHandler::onSendRedPacketEvent userId=%d ', $event->userId));
            LedService::getInstance()->buildSendRedPacketEvent($event);
        } catch (Exception $e) {
            Log::error(sprintf('LedEventHandler::onSendRedPacketEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

}