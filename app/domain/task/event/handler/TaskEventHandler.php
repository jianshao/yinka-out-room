<?php


namespace app\domain\task\event\handler;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\activity\luckStar\LuckStarService;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\event\TurntableEvent;
use think\facade\Log;
use Exception;

class TaskEventHandler
{
    private function onNewerHandler($userId, $event){
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $event) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $dailyService = $user->getTasks()->getNewerTask($event->timestamp);
                $dailyService->handleDomainEvent($event);
            });
        } catch (Exception $e) {
            Log::error(sprintf('TaskEventHandler::onNewerHandler userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
        }
    }

    private function onDailyHandler($userId, $event){
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $event) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $dailyService = $user->getTasks()->getDailyTask($event->timestamp);
                $dailyService->handleDomainEvent($event);
            });
        } catch (Exception $e) {
            Log::error(sprintf('TaskEventHandler::onDailyHandler userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
        }
    }

    public function onBreakBoxEvent($event) {
        Log::debug(sprintf('TaskEventHandler onBreakBoxEvent $userId=%d', $event->userId));

        $this->onNewerHandler($event->userId, $event);
    }

    public function onBreakBoxNewEvent($event) {
        Log::debug(sprintf('TaskEventHandler BreakBoxNewEvent $userId=%d', $event->userId));

        //new add 瓜分番茄豆活动
        $redis = RedisCommon::getInstance()->getRedis();
        $luckStarConfig = $redis->hGetAll('luck_star_config');
        if (!empty($luckStarConfig)) {
            if($event->timestamp >= strtotime($luckStarConfig['start_time']) && $event->timestamp < strtotime($luckStarConfig['end_time'])) {
                LuckStarService::getInstance()->luckyStarComes("breakBox",$event->boxId, $event->count, $luckStarConfig);
            }
        }

        $this->onNewerHandler($event->userId, $event);
    }

    public function onTurntableEvent(TurntableEvent $event){
        Log::debug(sprintf('TaskEventHandler onTurntableEvent $userId=%d', $event->userId));

        //new add 瓜分番茄豆活动
        $redis = RedisCommon::getInstance()->getRedis();
        $luckStarConfig = $redis->hGetAll('luck_star_config');
        if (!empty($luckStarConfig)) {
            if($event->timestamp >= strtotime($luckStarConfig['start_time']) && $event->timestamp <= strtotime($luckStarConfig['end_time'])) {
                LuckStarService::getInstance()->luckyStarComes("turntable",$event->boxId, $event->count, $luckStarConfig);
            }
        }

        $this->onNewerHandler($event->userId, $event);
    }

    public function onRoomCreateEvent($event) {
        Log::debug(sprintf('TaskEventHandler onRoomCreateEvent $userId=%d', $event->userId));

        $this->onNewerHandler($event->userId, $event);
    }

    public function onRoomShareEvent($event) {
        Log::debug(sprintf('TaskEventHandler onRoomShareEvent $userId=%d', $event->userId));

        $this->onDailyHandler($event->userId, $event);
    }

    public function onReleaseDynamicEvent($event) {
        Log::debug(sprintf('TaskEventHandler onReleaseDynamicEvent $userId=%d', $event->userId));

        $this->onDailyHandler($event->userId, $event);
    }

    public function onRoomStaySecondEvent($event) {
        Log::debug(sprintf('TaskEventHandler onRoomStaySecondEvent $userId=%d', $event->userId));

        $this->onDailyHandler($event->userId, $event);
    }

    public function onPrivateChatEvent($event) {
        Log::debug(sprintf('TaskEventHandler onPrivateChatEvent $userId=%d', $event->userId));

        $this->onDailyHandler($event->userId, $event);
    }

    public function onSendGiftEvent($event) {
        Log::debug(sprintf('TaskEventHandler onSendGiftEvent $userId=%d', $event->fromUserId));
        $this->onDailyHandler($event->fromUserId, $event);
    }

    public function onChargeEvent($event) {
        Log::debug(sprintf('TaskEventHandler onChargeEvent $userId=%d', $event->userId));
        $this->onDailyHandler($event->userId, $event);
    }

    public function onRoomAttentionEvent($event) {
        Log::debug(sprintf('TaskEventHandler onRoomAttentionEvent $userId=%d', $event->userId));

        $this->onDailyHandler($event->userId, $event);

        $this->onNewerHandler($event->userId, $event);
    }
}