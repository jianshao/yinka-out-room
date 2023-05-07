<?php


namespace app\domain\task\event\handler;


use app\domain\events\FocusFriendDomainEvent;
use app\domain\user\User;
use think\facade\Log;
use Exception;

class TaskDomainEventHandler
{
    private function onNewerHandler($event){
        try {
            $newerService = $event->user->getTasks()->getNewerTask($event->timestamp);
            $newerService->handleDomainEvent($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onNewerHandler userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    private function onDailyHandler($event){
        try {
            $dailyService = $event->user->getTasks()->getDailyTask($event->timestamp);
            $dailyService->handleDomainEvent($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onDailyHandler userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    public function onFocusFriendDomainEvent($event) {
        if ($event->isFocus!=1){
            return ;
        }
        try {
            Log::debug(sprintf('TaskDomainEventHandler onFocusFriendDomainEvent $userId=%d', $event->user->getUserId()));

            $this->onNewerHandler($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onFocusFriendDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    public function onUserLoginDomainEvent($event) {
        try {
            Log::debug(sprintf('TaskDomainEventHandler onUserLoginDomainEvent $userId=%d', $event->user->getUserId()));

            $tasks = $event->user->getTasks();
            $tasks->initTasks($event->timestamp);

            $this->onNewerHandler($event);
            $this->onDailyHandler($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onUserLoginDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    public function onBindMobileDomainEvent($event) {
        try {
            Log::debug(sprintf('TaskDomainEventHandler onBindMobileDomainEvent $userId=%d', $event->user->getUserId()));

            $this->onNewerHandler($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onBindMobileDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    public function onUserUpdateProfileDomainEvent($event) {
        try {
            Log::debug(sprintf('TaskDomainEventHandler onCompleteInfoDomainEvent $userId=%d', $event->user->getUserId()));

            $this->onNewerHandler($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onUserUpdateProfileDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    public function onCompleteNewerTaskDomainEvent($event) {
        try {
            Log::debug(sprintf('TaskDomainEventHandler onCompleteNewerTaskDomainEvent $userId=%d', $event->user->getUserId()));

            $this->onNewerHandler($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onCompleteNewerTaskDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    public function onCompleteRealUserDomainEvent($event) {
        try {
            Log::debug(sprintf('TaskDomainEventHandler onCompleteRealUserDomainEvent $userId=%d', $event->user->getUserId()));

            $this->onNewerHandler($event);
        } catch (Exception $e) {
            Log::warning(sprintf('TaskDomainEventHandler::onCompleteRealUserDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

}