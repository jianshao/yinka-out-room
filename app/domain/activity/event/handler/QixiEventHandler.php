<?php


namespace app\domain\activity\event\handler;


use app\common\RedisCommon;
use app\domain\activity\luckStar\LuckStarService;
use app\domain\activity\qixi\QixiService;
use app\domain\asset\AssetKindIds;
use app\domain\level\LevelService;
use app\event\BreakBoxEvent;
use app\event\BuyGoodsEvent;
use Exception;
use think\facade\Log;

class QixiEventHandler
{

    // 用户送礼增加等级经验值
    public function onSendGiftEvent($event) {
        try{
            QixiService::getInstance()->onSendGiftEvent($event);
        }catch (Exception $e) {
            Log::error(sprintf('QixiEventHandler::onSendGiftEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

    }
}