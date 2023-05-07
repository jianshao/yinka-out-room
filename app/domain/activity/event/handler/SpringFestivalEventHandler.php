<?php


namespace app\domain\activity\event\handler;

use app\common\RedisCommon;
use app\domain\activity\springFestival\SpringFestivalService;
use app\domain\exceptions\FQException;
use app\event\BreakBoxNewEvent;
use app\event\TurntableEvent;
use Exception;
use think\facade\Log;

class SpringFestivalEventHandler
{
    /**
     * @info  监听砸蛋事件
     * @param BreakBoxNewEvent $event
     */
    public function onBreakBoxNewEvent(BreakBoxNewEvent $event)
    {
        Log::debug(sprintf('ActivityEventHandler::onBreakBoxNewEvent $userId=%d', $event->userId));
        try {
            list($isRunning, $config) = SpringFestivalService::getInstance()->isRunning($event->userId, $event->timestamp);
            if ($isRunning) {
                //增加奖池值
                SpringFestivalService::getInstance()->incrPool("breakBox",$event->boxId, $event->count, $config);
                //todo 春联福字掉落
                SpringFestivalService::getInstance()->extractCouplet('breakBox', $event->boxId, $event->count, $config, $event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('ActivityEventHandler::onBreakBoxNewEvent userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @info 监听转盘事件
     * @param TurntableEvent $event
     */
    public function onTurntableEvent(TurntableEvent $event){
        Log::debug(sprintf('ActivityEventHandler::onBreakBoxNewEvent $userId=%d', $event->userId));
        try {
            list($isRunning, $config) = SpringFestivalService::getInstance()->isRunning($event->userId, $event->timestamp);
            if ($isRunning) {
                //增加奖池值
                SpringFestivalService::getInstance()->incrPool("turntable",$event->boxId, $event->count, $config);
                //todo 春联福字掉落
                SpringFestivalService::getInstance()->extractCouplet("turntable", $event->boxId, $event->count, $config, $event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('ActivityEventHandler::onBreakBoxNewEvent userId=%d ex=%d:%s file=%s:%d',
                $event->userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

}