<?php


namespace app\event\handler;


use app\common\amqp\core\AmpQueue;
use app\common\amqp\model\AmpTag;
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

/**
 * @info elastic 数据同步
 * Class ElasticSyncPublishHandler
 * @package app\event\handler
 */
class ElasticSyncPublishHandler
{

    /**
     * @Info 创建房间
     * @param RoomCreateEvent $event
     */
    public function onRoomCreateEvent(RoomCreateEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasticRoom, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onRoomCreateEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onRoomCreateEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @Info 修改房间
     * @param RoomUpdateEvent $event
     */
    public function onRoomUpdateEvent(RoomUpdateEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasticRoom, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onRoomUpdateEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onRoomUpdateEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 锁定房间
     * @param RoomLockEvent $event
     */
    public function onRoomLockEvent(RoomLockEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasticRoom, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onRoomLockEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onRoomLockEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 解锁房间
     * @param RoomUnlockEvent $event
     */
    public function onRoomUnlockEvent(RoomUnlockEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasticRoom, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onRoomUnlockEvent info userId=%d result=%d', $event->roomId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onRoomUnlockEvent userId=%d ex=%d:%s', $event->roomId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 内部接口修改工会房间
     * @param InnerRoomPartyEvent $event
     */
    public function onInnerRoomPartyEvent(InnerRoomPartyEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasticRoom, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onInnerRoomPartyEvent info userId=%d result=%d', $event->roomId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onInnerRoomPartyEvent userId=%d ex=%d:%s', $event->roomId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 用户登录
     * @param UserLoginEvent $event
     */
    public function onUserLoginEvent(UserLoginEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onUserLoginEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onUserLoginEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @Info 修改用户信息
     * @param UserUpdateProfileEvent $event
     */
    public function onUserUpdateProfileEvent(UserUpdateProfileEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onUserUpdateProfileEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onUserUpdateProfileEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 完善用户信息
     * @param PerfectUserInfoEvent $event
     */
    public function onPerfectUserInfoEvent(PerfectUserInfoEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onPerfectUserInfoEvent info userId=%d result=%d', $event->userModel->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onPerfectUserInfoEvent userId=%d ex=%d:%s', $event->userModel->userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @Info 爵位变更
     * @param DukeLevelChangeEvent $event
     */
    public function onDukeLevelChangeEvent(DukeLevelChangeEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onDukeLevelChangeEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onDukeLevelChangeEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @Info 等级变更
     * @param LevelChangeEvent $event
     */
    public function onLevelChangeEvent(LevelChangeEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onLevelChangeEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onLevelChangeEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @Info 用户信息审核
     * @param MemberDetailAuditEvent $event
     */
    public function onMemberDetailAuditEvent(MemberDetailAuditEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onMemberDetailAuditEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onMemberDetailAuditEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 公会后台成员审核
     * @param GhAuditMemberEvent $event
     */
    public function onGhAuditMemberEvent(GhAuditMemberEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onGhAuditMemberEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onGhAuditMemberEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 用户注册
     * @param UserRegisterEvent $event
     */
    public function onUserRegisterEvent(UserRegisterEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onUserRegisterEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onUserRegisterEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 用户注销
     * @param UserCancelEvent $event
     */
    public function onUserCancelEvent(UserCancelEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onUserCancelEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onUserCancelEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 内部接口公会成员审核
     * @param InnerAuditMemberEvent $event
     */
    public function onInnerAuditMemberEvent(InnerAuditMemberEvent $event)
    {
        try {
            $result = AmpQueue::getInstance()->storePublishTagEvent(AmpTag::$messageBusElasTicUser, $event);
            Log::info(sprintf('ElasticSyncPublishHandler::onInnerAuditMemberEvent info userId=%d result=%d', $event->userId, $result));
        } catch (Exception $e) {
            Log::error(sprintf('ElasticSyncPublishHandler::onInnerAuditMemberEvent userId=%d ex=%d:%s', $event->userId, $e->getCode(), $e->getMessage()));
        }
    }


}