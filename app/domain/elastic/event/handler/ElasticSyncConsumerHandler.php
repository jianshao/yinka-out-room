<?php


namespace app\domain\elastic\event\handler;


use app\domain\elastic\service\ElasticService;
use app\domain\user\event\PerfectUserInfoEvent;
use app\event\DukeLevelChangeEvent;
use app\event\GhAuditMemberEvent;
use app\event\InnerAuditMemberEvent;
use app\event\InnerRoomPartyEvent;
use app\event\LevelChangeEvent;
use app\event\MemberDetailAuditEvent;
use app\event\RoomCreateEvent;
use app\event\RoomLockEvent;
use app\event\RoomUnlockEvent;
use app\event\RoomUpdateEvent;
use app\event\UserCancelEvent;
use app\event\UserLoginEvent;
use app\event\UserRegisterEvent;
use app\event\UserUpdateProfileEvent;
use Exception;
use think\facade\Log;

class ElasticSyncConsumerHandler
{
    /**
     * @info 创建房间
     * @param $event RoomCreateEvent
     */
    public function onRoomCreateEvent(RoomCreateEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncRoomModel($event->roomId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onRoomCreateEvent info roomId=%d result=%d', $event->roomId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onRoomCreateEvent roomId=%d ex=%d:%s', $event->roomId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @Info 修改房间
     * @param RoomUpdateEvent $event
     */
    public function onRoomUpdateEvent(RoomUpdateEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncRoomModel($event->roomId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onRoomUpdateEvent info roomId=%d result=%d', $event->roomId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onRoomUpdateEvent roomId=%d ex=%d:%s', $event->roomId, $e->getCode(), $e->getTraceAsString()));
        }
    }


    /**
     * @Info 锁定房间
     * @param RoomLockEvent $event
     */
    public function onRoomLockEvent(RoomLockEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncRoomModel($event->roomId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onRoomLockEvent info roomId=%d result=%d', $event->roomId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onRoomLockEvent roomId=%d ex=%d:%s', $event->roomId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @Info 锁定房间
     * @param RoomUnlockEvent $event
     */
    public function onRoomUnlockEvent(RoomUnlockEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncRoomModel($event->roomId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onRoomUnlockEvent info roomId=%d result=%d', $event->roomId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onRoomUnlockEvent roomId=%d ex=%d:%s', $event->roomId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @info 内部接口修改工会房间
     * @param InnerRoomPartyEvent $event
     */
    public function onInnerRoomPartyEvent(InnerRoomPartyEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncRoomModel($event->roomId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onRoomUnlockEvent info roomId=%d result=%d', $event->roomId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onRoomUnlockEvent roomId=%d ex=%d:%s', $event->roomId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @Info 用户登录
     * @param UserLoginEvent $event
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onUserLoginEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onUserLoginEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }


    /**
     * @Info 修改用户信息
     * @param UserUpdateProfileEvent $event
     */
    public function onUserUpdateProfileEvent(UserUpdateProfileEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onUserUpdateProfileEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onUserUpdateProfileEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @Info 完善用户信息
     * @param PerfectUserInfoEvent $event
     */
    public function onPerfectUserInfoEvent(PerfectUserInfoEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userModel->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onPerfectUserInfoEvent info userId=%d result=%d', $event->userModel->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onPerfectUserInfoEvent userId=%d ex=%d:%s', $event->userModel->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }


    /**
     * @Info 爵位等级变更
     * @param DukeLevelChangeEvent $event
     */
    public function onDukeLevelChangeEvent(DukeLevelChangeEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onDukeLevelChangeEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onDukeLevelChangeEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }


    /**
     * @Info 等级变化
     * @param LevelChangeEvent $event
     */
    public function onLevelChangeEvent(LevelChangeEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onLevelChangeEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onLevelChangeEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @Info 用户信息审核
     * @param MemberDetailAuditEvent $event
     */
    public function onMemberDetailAuditEvent(MemberDetailAuditEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onMemberDetailAuditEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onMemberDetailAuditEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }


    /**
     * @Info 公会后台成员审核
     * @param GhAuditMemberEvent $event
     */
    public function onGhAuditMemberEvent(GhAuditMemberEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onGhAuditMemberEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onGhAuditMemberEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @info 内部接口公会成员审核
     * @param InnerAuditMemberEvent $event
     */
    public function onInnerAuditMemberEvent(InnerAuditMemberEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onInnerAuditMemberEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onInnerAuditMemberEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @info 用户注册
     * @param UserRegisterEvent $event
     */
    public function onUserRegisterEvent(UserRegisterEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onUserRegisterEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onUserRegisterEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }

    /**
     * @info 用户注册
     * @param UserCancelEvent $event
     */
    public function onUserCancelEvent(UserCancelEvent $event)
    {
        try {
            $result = ElasticService::getInstance()->syncUserModel($event->userId);
            Log::info(sprintf('ElasticSyncConsumerHandler::onUserCancelEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncConsumerHandler::onUserCancelEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getTraceAsString()));
        }
    }


}