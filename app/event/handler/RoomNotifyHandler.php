<?php

namespace app\event\handler;

use app\common\RedisCommon;
use app\domain\game\box\service\BoxService;
use app\domain\game\box2\Box2Service;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomTypeModelDao;
use app\domain\room\model\RoomManagerModel;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\event\InnerRoomPartyEvent;
use app\event\LevelChangeEvent;
use app\event\MemberDetailAuditEvent;
use app\event\RoomAttentionEvent;
use app\event\RoomManagerAddEvent;
use app\event\RoomUpdateEvent;
use app\service\RoomNotifyService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use Exception;
use think\facade\Log;

class RoomNotifyHandler
{
    public function onPropTypeActionEvent($event)
    {
        try {
            RoomNotifyService::getInstance()->notifySyncUserData($event->userId);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onPropTypeActionEvent userId=%d typeName=%s action=%s ex=%d:%s',
                $event->userId, $event->typeName, $event->action, $e->getCode(), $e->getMessage()));
        }
    }

    public function onSendGiftEvent($event)
    {
        if ($event->roomId > 0) {
            try {
                RoomNotifyService::getInstance()->notifySendGift($event->roomId, $event->fromUserId,
                    $event->giftKind, $event->count, $event->sendDetails, $event->receiveDetails, $event->fromBag);

//                // 送礼物麦上魅力值变化
//                RoomNotifyService::getInstance()->notifyMicCharm($event->roomId, $event->fromUserId,
//                    $event->giftKind, $event->count, $event->sendDetails, $event->receiveDetails, $event);
            } catch (Exception $e) {
                Log::error(sprintf('RoomNotifyHandler::onSendGiftEvent userId=%d roomId=%d ex=%d:%s',
                    $event->fromUserId, $event->roomId,
                    $e->getCode(), $e->getMessage()));
            }
        }
    }

    /**
     * @param $event LevelChangeEvent
     * */
    public function onLevelChangeEvent($event)
    {
        try {
            RoomNotifyService::getInstance()->notifySyncUserData($event->userId);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onLevelChangeEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    public function onBuyVipEvent($event)
    {
        try {
            Log::info(sprintf('RoomNotifyHandler::onBuyVipEvent userId=%d vipLevel=%d', $event->userId, $event->vipLevel));
            RoomNotifyService::getInstance()->notifySyncUserData($event->userId, null);
            RoomNotifyService::getInstance()->notifyBuyVip($event);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onBuyVipEvent userId=%d vipLevel=%d ex=%d:%s',
                $event->userId, $event->vipLevel,
                $e->getCode(), $e->getMessage()));
        }
    }

    public function onUserUpdateProfileEvent($event)
    {
        try {
            if (array_key_exists('nickname', $event->profile)
                || array_key_exists('avatar', $event->profile)) {
                RoomNotifyService::getInstance()->notifySyncUserData($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onUserUpdateProfileEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    public function onRoomLockEvent($event)
    {
        try {
            RoomNotifyService::getInstance()->notifyRoomLock($event->roomId, true);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomLockEvent roomId=%d ex=%d:%s',
                $event->roomId, $e->getCode(), $e->getMessage()));
        }
    }

    public function onRoomUnlockEvent($event)
    {
        try {
            RoomNotifyService::getInstance()->notifyRoomLock($event->roomId, false);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomUnlockEvent roomId=%d ex=%d:%s',
                $event->roomId, $e->getCode(), $e->getMessage()));
        }
    }

    public function onInnerRoomPartyEvent(InnerRoomPartyEvent $event)
    {
        // 通知房间服务器房间变化
        try {
            $notifyType = 'baseData';
            if (array_key_exists('roomType', $event->profile)) {
                $notifyType = 'mode';
            }

            RoomNotifyService::getInstance()->notifySyncRoomData($event->roomId, $notifyType);

            // 通知房间用户
            $roomModel = RoomModelDao::getInstance()->loadRoom($event->roomId);
            $roomType = RoomTypeModelDao::getInstance()->loadRoomType($roomModel->roomType);
            $isChangeRoom = false;
            RoomNotifyService::getInstance()->notifyEditRoomType($event->roomId, $roomModel, $roomType, $isChangeRoom);
            if (array_key_exists('backgroundImage', $event->profile)) {
                //发消息操作
                RoomNotifyService::getInstance()->notifyRoomBackgroundImageUpdate($event->roomId, $event->profile['backgroundImage']);
            }
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomUpdateEvent roomId=%d ex=%d:%s',
                $event->roomId, $e->getCode(), $e->getMessage()));
        }
    }


    public function onRoomUpdateEvent(RoomUpdateEvent $event)
    {
        // 通知房间服务器房间变化
        try {
            $notifyType = 'baseData';
            if (array_key_exists('roomType', $event->profile)) {
                $notifyType = 'mode';
            }

            RoomNotifyService::getInstance()->notifySyncRoomData($event->roomId, $notifyType);

            // 通知房间用户
            $roomModel = RoomModelDao::getInstance()->loadRoom($event->roomId);
            $roomType = RoomTypeModelDao::getInstance()->loadRoomType($roomModel->roomType);
            $isChangeRoom = false;
            RoomNotifyService::getInstance()->notifyEditRoomType($event->roomId, $roomModel, $roomType, $isChangeRoom);
            if (array_key_exists('backgroundImage', $event->profile)) {
                //发消息操作
                RoomNotifyService::getInstance()->notifyRoomBackgroundImageUpdate($event->roomId, $event->profile['backgroundImage']);
            }
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomUpdateEvent roomId=%d ex=%d:%s',
                $event->roomId, $e->getCode(), $e->getMessage()));
        }
    }

    public function onRoomAttentionEvent($event)
    {
        try {
            RoomNotifyService::getInstance()->notifyAttentionRoom($event->roomId, $event->userId);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onAttentionRoomEvent userId=%d roomId=%d ex=%d:%s',
                $event->userId, $event->roomId, $e->getCode(), $e->getMessage()));
        }
    }

    public function calcLongTimeStr($longTime)
    {
        if ($longTime == 600) {
            return '10min';
        } elseif ($longTime == 1800) {
            return '30min';
        } elseif ($longTime == 3600) {
            return '60min';
        }
        return '永久';
    }

    public function onRoomBanUserEvent($event)
    {
        try {
            RoomNotifyService::getInstance()->notifyBan($event->roomId, $event->userId, $event->longTime, $event->ban);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomBlackUserEvent userId=%d roomId=%d longTime=%d ex=%s',
                $event->userId, $event->roomId, $event->longTime, $e->getTraceAsString()));
        }
    }

    public function onRoomBlackUserEvent($event)
    {
        try {
            $minute = $this->calcLongTimeStr($event->longTime);
            RoomNotifyService::getInstance()->notifyKickout($event->roomId, $event->userId);

            $blackUserName = UserModelDao::getInstance()->findNicknameByUserId($event->userId);
            $opUserName = UserModelDao::getInstance()->findNicknameByUserId($event->opUserId);

            if ($minute == '永久') {
                $content = sprintf('用户%s被用户%s永久踢出房间',
                    $blackUserName,
                    $opUserName);
            } else {
                $content = sprintf('用户%s被用户%s踢出房间',
                    $blackUserName,
                    $opUserName);
            }

            $managerList = [];
            $managers = RoomManagerModelDao::getInstance()->loadAllManager($event->roomId);
            foreach ($managers as $manager){
                $managerList[] = $manager->userId;
            }

            $roomOnline = RedisCommon::getInstance()->getRedis()->hGetAll('go_room_' . $event->roomId);
            $roomOnline = array_keys($roomOnline);

            array_push($managerList, $event->roomOwnerUserId);

            $commonMsg = json_encode(['msgIds' => 2052, 'content' => $content]);

            foreach ($managerList as $k => $v) {
                if (!in_array($v, $roomOnline)) {
                    continue;
                }

                $msg['msg'] = $commonMsg;
                $msg['roomId'] = $event->roomId;
                $msg['toUserId'] = strval($v);
                RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msg);
            }
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomBlackUserEvent userId=%d roomId=%d ex=%s',
                $event->userId, $event->roomId, $e->getTraceAsString()));
        }
    }

    /**
     * @param $event RoomManagerAddEvent
     * */
    public function onRoomManagerAddEvent($event)
    {
        try {
            //发消息操作
            RoomNotifyService::getInstance()->notifySyncRoomData($event->roomId, 'manager');

            $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);

            $msg = [
                'msg' => json_encode([
                    'msgId' => 2055,
                    'UserId' => $event->userId,
                    'Name' => $userModel->nickname,
                    'UserLevel' => $userModel->lvDengji,
                    'RoomGuardLevel' => 4,
                    'IsManager' => 1,
                    'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                    'dukeLevel' => $userModel->dukeLevel,
                    'userIdentity' => ArrayUtil::safeGet(RoomManagerModel::$viewType, $event->userIdentity, 0),
                    'isVip' => $userModel->vipLevel,
                    'VipLevel' => $userModel->vipLevel,
                ]),
                'roomId' => $event->roomId,
                'toUserId' => '0'
            ];

            RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msg);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomManagerAddEvent userId=%d roomId=%d ex=%s',
                $event->userId, $event->roomId, $e->getTraceAsString()));
        }
    }

    public function onRoomManagerRemoveEvent($event)
    {
        try {
            //发消息操作
            RoomNotifyService::getInstance()->notifySyncRoomData($event->roomId, 'manager');

            $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);

            $msg = [
                'msg' => json_encode([
                    'msgId' => 2055,
                    'UserId' => $event->userId,
                    'Name' => $userModel->nickname,
                    'UserLevel' => $userModel->lvDengji,
                    'RoomGuardLevel' => 4,
                    'IsManager' => 0,
                    'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                    'dukeLevel' => $userModel->dukeLevel,
                    'userIdentity' => 0,
                    'isVip' => $userModel->vipLevel,
                    'VipLevel' => $userModel->vipLevel
                ]),
                'roomId' => $event->roomId,
                'toUserId' => '0'
            ];

            RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msg);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onRoomManagerAddEvent userId=%d roomId=%d ex=%s',
                $event->userId, $event->roomId, $e->getTraceAsString()));
        }
    }

    public function onThreeLootNoticeEvent($event)
    {
        try {
            $tmpShowType = $event->tableId == 3 ? 0 : 1;  // 0飘屏 1公屏
            $userModel = UserModelDao::getInstance()->loadUserModel($event->order->seatInfos[$event->order->winnerIndex]->userId);
            $msg = [
                'msg' => json_encode([
                    'msgId' => 2089,
                    'items' => [
                        'userId' => $userModel->userId,
                        'prettyId' => $userModel->prettyId,
                        'userLevel' => $userModel->lvDengji,
                        'nickName' => $userModel->nickname,
                        'isVip' => $userModel->vipLevel,
                        'dukeId' => $userModel->dukeLevel,
                        'typeId' => $event->tableId,
                        'giftId' => $event->order->giftId,
                        'giftName' => $event->order->giftName,
                        'giftImage' => CommonUtil::buildImageUrl($event->order->giftImage),
                        'giftCount' => 1,
                        'showType' => $tmpShowType,
                    ]
                ]),
                'roomId' => 0,
                'toUserId' => '0'
            ];
            RoomNotifyService::getInstance()->notifyRoomMsg(0, $msg);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onThreeLootNoticeEvent tableId=%d ex=%s',
                $event->tableId, $e->getTraceAsString()));
        }
    }

    public function onBreakBoxEvent($event)
    {
        try {
            Log::info(sprintf('RoomNotifyHandler::onBreakBoxEvent event=%s', json_encode($event)));
            BoxService::getInstance()->packageScreenMessage($event);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onBreakBoxEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    public function onBreakBoxNewEvent($event)
    {
        try {
            Log::info(sprintf('RoomNotifyHandler::onBreakBoxNewEvent event=%s', json_encode($event)));
            Box2Service::getInstance()->packageScreenMessage($event);
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onBreakBoxNewEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }


    /**
     * @info 用户信息审核 通知房间socket刷新用户数据
     * @param MemberDetailAuditEvent $event
     */
    public function onMemberDetailAuditEvent(MemberDetailAuditEvent $event)
    {
        try {
            if (in_array($event->memberDetailAuditModel->action, [MemberDetailAuditActionModel::$avatar, MemberDetailAuditActionModel::$nickname])) {
                RoomNotifyService::getInstance()->notifySyncUserData($event->userId);
            }
        } catch (Exception $e) {
            Log::error(sprintf('RoomNotifyHandler::onMemberDetailAuditEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

}