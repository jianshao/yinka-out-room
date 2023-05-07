<?php


namespace app\domain\activity\event\handler;


use app\domain\activity\christmas\ChristmasService;
use app\domain\activity\guoqing\GuoQingService;
use app\domain\activity\halloween\service\HalloweenService;
use app\domain\activity\weekStar\ZhouXingService;
use app\domain\activity\yearTicket\YearTicketService;
use app\domain\exceptions\FQException;
use app\event\BreakBoxNewEvent;
use app\event\TradeUnionAgentEvent;
use app\event\TurntableEvent;
use Exception;
use think\facade\Log;

class ActivityEventHandler
{

    // 用户送礼增加等级经验值
    public function onSendGiftEvent($event)
    {
        Log::info(sprintf('ActivityEventHandler::onSendGiftEvent entry event=%s',
            json_encode($event)));
        try {
//            ChristmasService::getInstance()->onSendGiftEvent($event);

//            GiftReturnService::getInstance()->onSendGiftEvent($event);

//            ZhongQiuService::getInstance()->onSendGiftEvent($event);

//            ZhongQiuPKService::getInstance()->onSendGiftEvent($event);

//            GuoQingService::getInstance()->onSendGiftEvent($event);

            //闪耀之星你最红活动 豪气之王、闪耀之星 周榜单数据
//            ZhouXingService::getInstance()->addWeekStarInfo($event);

            HalloweenService::getInstance()->onSendGiftEvent($event);



        } catch (Exception $e) {
            Log::error(sprintf('ActivityEventHandler::onSendGiftEvent userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

//        try {
//            //送礼时，触发年票活动
//            YearTicketService::getInstance()->onSendGiftEvent($event);
//        } catch (Exception $e) {
//            Log::error(sprintf('ActivityEventHandler::onSendGiftEvent userId=%d ex=%d:%s file=%s:%d',
//                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
//        }
    }

    public function onForumCheckPassEvent($event)
    {
        try {

            GuoQingService::getInstance()->onForumCheckPassEvent($event);

        } catch (Exception $e) {
            Log::error(sprintf('ActivityEventHandler::onForumCheckPassEvent userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }


//    /**
//     * @param $event
//     */
//    public function onChargeEvent($event)
//    {
//        try {
//            HalloweenService::getInstance()->onChargeEvent($event);
//        } catch (Exception $e) {
//            if ($e instanceof FQException && $e->getCode() === 513) {
//                return;
//            }
//            Log::error(sprintf('ActivityEventHandler::onChargeEvent userId=%d ex=%d:%s file=%s:%d',
//                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
//        }
//    }

//    /**
//     * @info 工会代充
//     * @param $event
//     */
//    public function onTradeUnionAgentEvent(TradeUnionAgentEvent $event)
//    {
//        try {
//            HalloweenService::getInstance()->onTradeUnionAgentEvent($event);
//        } catch (Exception $e) {
//            if ($e instanceof FQException && $e->getCode() === 513) {
//                return;
//            }
//            Log::error(sprintf('ActivityEventHandler::onTradeUnionAgentEvent userId=%d ex=%d:%s file=%s:%d',
//                $event->toUid, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
//        }
//    }


    /**
     * @info  监听砸蛋事件
     * @param BreakBoxNewEvent $event
     */
    public function onBreakBoxNewEvent(BreakBoxNewEvent $event)
    {
        Log::debug(sprintf('ActivityEventHandler::onBreakBoxNewEvent $userId=%d', $event->userId));
        try {
//            年票活动event
            YearTicketService::getInstance()->onBreakBoxNewEvent($event);
        } catch (Exception $e) {
            Log::error(sprintf('ActivityEventHandler::onBreakBoxNewEvent userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @info 监听转盘事件
     * @param TurntableEvent $event
     */
    public function onTurntableEvent(TurntableEvent $event)
    {
        Log::debug(sprintf('ActivityEventHandler::onBreakBoxNewEvent $userId=%d', $event->userId));
        try {
//            年票活动event
            YearTicketService::getInstance()->onTurntableEvent($event);
        } catch (Exception $e) {
            Log::error(sprintf('ActivityEventHandler::onBreakBoxNewEvent userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

}