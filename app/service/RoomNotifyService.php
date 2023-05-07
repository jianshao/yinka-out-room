<?php

namespace app\service;

use app\domain\asset\AssetKindIds;
use app\domain\duke\DukeSystem;
use app\domain\guild\cache\GuildRoomCache;
use app\domain\prop\PropKindBubble;
use app\domain\queue\producer\NotifyMessage;
use app\domain\room\dao\HomeHotRoomModelDao;
use app\domain\room\dao\RecreationHotModelDao;
use app\domain\room\dao\RoomHotValueDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\WholewheatGiftPointModelDao;
use app\domain\room\model\RoomModel;
use app\domain\room\model\RoomTypeModel;
use app\domain\user\dao\UserModelDao;
use app\query\prop\service\PropQueryService;
use app\query\site\service\SiteService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\facade\Log;

class RoomNotifyService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomNotifyService();
        }
        return self::$instance;
    }

    public function notifySyncUserData($userId)
    {
        $msg = ['userId' => (int)$userId];
        $socket_url = config('config.socket_url_base') . 'iapi/syncUserData';
        $msgData = json_encode($msg);
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $socket_url, 'data' => $msgData, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifySyncUserData userId=%d data=%s resMsg=%s', $userId, $msgData, $resMsg));
    }

    public function notifyRoomLock($roomId, $locked)
    {
        $msg = [
            'roomId' => intval($roomId),
            'lock' => $locked ? 1 : 2
        ];
        $data = json_encode($msg);
        $url = config('config.socket_url_base') . 'iapi/lockRoom';
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifyRoomLock roomId=%d locked=%d data=%s resMsg=%s', $roomId, $locked, $data, $resMsg));
    }

    public function notifySyncRoomData($roomId, $type)
    {
        $msg = ['roomId' => (int)$roomId, 'type' => $type];
        $url = config('config.socket_url_base') . 'iapi/syncRoomData';
        $data = json_encode($msg);
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifySyncRoomData roomId=%d type=%s data=%s resMsg=%s', $roomId, $type, $data, $resMsg));
    }

    public function notifyRoomBackgroundImageUpdate($roomId, $backgroundImage)
    {
        if (empty($backgroundImage)) {
            return;
        }

        $msg = [
            'msg' => json_encode([
                'msgId' => 2051,
                'room_bg' => CommonUtil::buildImageUrl($backgroundImage)
            ]),
            'roomId' => $roomId,
            'toUserId' => '0'
        ];

        $this->notifyRoomMsg($roomId, $msg);
    }

    public function notifyEditRoomType($roomId, RoomModel $roomModel, $roomType, $isChangeRoom)
    {
        $msg = [
            'msg' => json_encode([
                'msgId' => 2050,
                'room_name' => $roomModel->name,
                'room_desc' => $roomModel->guildId > 0 ? $roomModel->desc : '',
                'room_welcomes' => $roomModel->guildId > 0 ? $roomModel->welcomes : '',
                'modeName' => $roomModel->guildId == 0 ? $roomType->roomMode : '',
                'isChangeRoom' => $isChangeRoom,
                'ModeId' => $roomType->id,
                'ModePid' => $roomType->pid
            ]),
            'roomId' => $roomId,
            'toUserId' => '0'
        ];

        $this->notifyRoomMsg($roomId, $msg);
    }

    public function notifyRoomCharmUpdate($roomId, $heartValueList)
    {
        $msg = [
            'msg' => json_encode([
                'msgId' => 37,
                'HeartValueList' => $heartValueList,
            ]),
            'roomId' => $roomId,
            'toUserId' => '0'
        ];

        return $this->notifyRoomMsg($roomId, $msg);
    }

    public function notifyRoomMsg($roomId, $msg)
    {
        $url = config('config.socket_url');
        $data = json_encode($msg);
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifyRoomMsg roomId=%d data=%s resMsg=%s', $roomId, $data, $resMsg));
        return true;
    }

    public function notifyRoomLedMsg($roomId, $msg)
    {
        $url = config('config.socket_url_base') . 'iapi/broadcastLed';
        $data = json_encode($msg);
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifyRoomMsg roomId=%d data=%s resMsg=%s', $roomId, $data, $resMsg));
        return true;
    }

    public function notifyRoomMsgLite($roomId, $msg)
    {
        $url = config('config.socket_url');
        $data = json_encode($msg);
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('notifyRoomMsgLite roomId=%d data=%s resMsg=%s', $roomId, $data, $resMsg));
        return true;
    }

    public function notifyBan($roomId, $userId, $longTime, $ban)
    {
        $msg = [
            'roomId' => $roomId,
            'toUserId' => '' . $userId,
            'duration' => $longTime,
            'isDisabled' => $ban ? 1 : 2
        ];
        $url = config('config.socket_url_base') . 'iapi/disableMsg';
        $data = json_encode($msg);
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifyBan roomId=%d userId=%d longTime=%d ban=%d data=%s resMsg=%s',
            $roomId, $userId, $longTime, $ban, $data, $resMsg));
    }

    public function notifyKickout($roomId, $userId)
    {
        $msg = [
            'roomId' => $roomId,
            'toUserId' => '' . $userId,
        ];
        $data = json_encode($msg);
        $url = config('config.socket_url_base') . 'iapi/kickout';
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifyKickout roomId=%d userId=%d data=%s resMsg=%s', $roomId, $userId, $data, $resMsg));
    }

    public function notifyKickoutToManager($roomId, $userId)
    {
        $msg = [
            'roomId' => $roomId,
            'toUserId' => $userId,
        ];
        $data = json_encode($msg);
        $url = config('config.socket_url_base') . 'iapi/kickout';
        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $data, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('NotifyKickout roomId=%d userId=%d data=%s resMsg=%s', $roomId, $userId, $data, $resMsg));
    }

    public function notifyAttentionRoom($roomId, $userId)
    {
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($roomId, $userId);
        $msg = [
            'msgId' => 2053,
            'uid' => $userId,
            'nickname' => $userModel->nickname,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'userIdentity' => $userIdentity,
            'dukeLevel' => $userModel->dukeLevel,
            'description' => 'attention',
            'level' => $userModel->lvDengji,
            'prettyid' => $userModel->prettyId,
            'isVip' => $userModel->vipLevel
        ];
        $msg['msg'] = json_encode($msg);
        $msg['roomId'] = $roomId;
        $msg['toUserId'] = '0';
        $this->notifyRoomMsg($roomId, $msg);
    }

    public function notifyRoomIsWheatUpdate($roomId, $isWheat)
    {
        $msg['msg'] = json_encode([
            'msgId' => 2052,
            'inviteWheat' => true
        ]);
        $msg['roomId'] = $roomId;
        $msg['toUserId'] = '0';
        $this->notifyRoomMsg($roomId, $msg);
    }

    public function incGiftHotGetValue($roomId, $guildId, $hotValue, $fromBag)
    {
        if (!$fromBag) {
//            增加热度值
            RoomHotValueDao::getInstance()->incGiftHotValue($roomId, $guildId, $hotValue);
//            增加首页人气值
            HomeHotRoomModelDao::getInstance()->incGiftHotForBean($roomId, $guildId, $hotValue);
        }
        $model = new GuildRoomCache($roomId);
        $redu = $model->getHotSumTpl();
        return $redu ? $redu : 0;
    }

    public function testIncrPopularValue()
    {
        $roomId = 133937;
//        $giftId = 383;
//        $giftId = 507;
        $giftId = 513;
        $re = $this->incrPopularValue($roomId, $giftId);
        var_dump($re);
        die;
    }


    /**
     * @param $giftId
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function loadGiftIdToHotValue($giftId)
    {
        $pointModel = WholewheatGiftPointModelDao::getInstance()->loadForGiftId($giftId);
        if ($pointModel === null) {
            return 0;
        }
        return $pointModel->point;
    }

    /**
     * @param $roomId
     * @param $giftId
     * @return bool
     */
    public function incrPopularValue($roomId, $giftId)
    {
        if (empty($roomId) || empty($giftId)) {
            return false;
        }
        $pointValue = $this->loadGiftIdToHotValue($giftId);
        if ($pointValue === 0) {
            return false;
        }
//            增加娱乐页人气值
        RecreationHotModelDao::getInstance()->incGiftHotForWholewheat($roomId, 0, $pointValue);
        return true;
    }


    public function notifySendGift($roomId, $fromUserId, $giftKind, $count, $sendDetails, $receiveDetails, $fromBag = false)
    {
        $fromUserModel = UserModelDao::getInstance()->loadUserModel($fromUserId);
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        $giftCoin = $giftKind->getPriceByAssetId(AssetKindIds::$BEAN);
        $beanValue = $giftCoin * $count * count($sendDetails);
//        $hotValue = $roomModel->guildId > 0 ? $beanValue * 100 : $beanValue * 100 * 0.4;
        $hotValue = $beanValue;
        $roomHotValue = $this->incGiftHotGetValue($roomId, $roomModel->guildId, $hotValue, $fromBag);

        $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($roomId, $fromUserId);

        $bubble = PropQueryService::getInstance()->getWaredProp($fromUserId, PropKindBubble::$TYPE_NAME);
        $msg2030 = [
            'msgId' => 2030,
            'roomId' => $roomModel->roomId,
            'RoomName' => $roomModel->name,
            'VisitorNum' => $roomHotValue,
            'GiftData' => [
                'Price' => $giftCoin,
                'Charm' => $giftKind->deliveryCharm,
                'Name' => $giftKind->name,
                'Image' => CommonUtil::buildImageUrl($giftKind->image),
                'GiftAnimation' => CommonUtil::buildImageUrl($giftKind->giftAnimation),
                'Animation' => CommonUtil::buildImageUrl($giftKind->animation),
                'ClassType' => $giftKind->classType,
                'giftMp4Animation' => CommonUtil::buildImageUrl($giftKind->giftMp4Animation),
                'mp4Rate' => $giftKind->mp4Rate,
            ],
            'Count' => $count,
            'GiftId' => '' . $giftKind->kindId,
            'fromBag' => $fromBag,
            'GiftGiver' => [
                'Name' => $fromUserModel->nickname,
                'UserId' => '' . $fromUserId,
                'HeadImageUrl' => CommonUtil::buildImageUrl($fromUserModel->avatar),
                'PrettyId' => $fromUserModel->prettyId,
                'UserLevel' => $fromUserModel->lvDengji,
                'dukeLevel' => $fromUserModel->dukeLevel,
                'userIdentity' => $userIdentity,
                'isVip' => $fromUserModel->vipLevel,
                'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                'bubble' => PropQueryService::getInstance()->encodeBubbleInfo($bubble),
            ]
        ];

        $giveGiftDatas = [];
        $receiveUserModelMap = [];
        $charmDatas = [];
        $needChangeMicId = count($receiveDetails) == 1 ? true : false;
        foreach ($receiveDetails as $item) {
            $receiveUser = $item[0];
            $userModel = UserModelDao::getInstance()->loadUserModel($receiveUser->userId);
            $receiveUserModelMap[$userModel->userId] = $userModel;
            $giveGiftDatas[] = [
                'micId' => $receiveUser->micId,
                'userId' => $receiveUser->userId,
                'TargetName' => $userModel->nickname,
                'TargetHeadImageUrl' => CommonUtil::buildImageUrl($userModel->avatar),
                'isVip' => $userModel->vipLevel,
                'attires' => null,
            ];
            $micId = $receiveUser->micId;
//            if ($needChangeMicId && $receiveUser->userId == $roomModel->userId) {
//                $micId = 999;
//            }
            $deliveryCharm = 0;
            foreach ($item[1] as $giftDetails) {
                $deliveryCharm += $giftDetails->deliveryGiftKind->deliveryCharm * $giftDetails->count;
            }
            $charmDatas[$micId] = $deliveryCharm;
        }

        CharmService::getInstance()->addCharm($roomId, $charmDatas);

        $msg2030['GiveGiftDatas'] = $giveGiftDatas;

        $this->notifyRoomMsg($roomId, [
            'msg' => json_encode($msg2030),
            'roomId' => $roomModel->roomId,
            'toUserId' => '0'
        ]);

        $siteConf = SiteService::getInstance()->getSiteConf(1);
        if ($giftKind->isBox()) {
            foreach ($receiveDetails as $item) {
                $allRoomDatas = [];
                $items = [];
                $receiveUser = $item[0];
                $receiveUserModel = ArrayUtil::safeGet($receiveUserModelMap, $receiveUser->userId);
                foreach ($item[1] as $giftDetails) {
                    $itemData = [
                        'roomName' => $roomModel->name,
                        'showType' => 1,
                        'giftId' => $giftDetails->deliveryGiftKind->kindId,
                        'giftName' => $giftDetails->deliveryGiftKind->name,
                        'giftUrl' => CommonUtil::buildImageUrl($giftDetails->deliveryGiftKind->image),
                        'count' => $giftDetails->count,
                    ];
                    $items[] = $itemData;

                    Log::info(sprintf('RoomNotifyService::notifySendGift roomId=%d fromUserId=%d giftId=%d count=%d receiveUserId=%d receiveGiftId=%d eggcoin=%d send_gift_num=%d price=%d',
                        $roomModel->roomId, $fromUserId, $giftKind->kindId, $count, $receiveUser->userId,
                        $giftDetails->deliveryGiftKind->kindId, $siteConf['eggcoin'], $siteConf['send_gift_num'],
                        $giftDetails->deliveryGiftKind->price != null ? $giftDetails->deliveryGiftKind->price->count : 0));
                    //判断全服公屏
                    if ($giftDetails->deliveryGiftKind->price != null
                        && $giftDetails->deliveryGiftKind->price->count >= $siteConf['eggcoin']
                        && $giftDetails->deliveryGiftKind->price->count < $siteConf['send_gift_num']) {
                        $allRoomDatas[] = $itemData;
                    }

                    //判断飘屏
                    if ($giftDetails->deliveryGiftKind->price != null
                        && $giftDetails->deliveryGiftKind->price->count >= $siteConf['send_gift_num']) {
                        $msg2083 = [
                            'msgId' => 2083,
                            'roomName' => $roomModel->name,
                            'giftId' => $giftDetails->deliveryGiftKind->kindId,
                            'giftName' => $giftDetails->deliveryGiftKind->name,
                            'giftUrl' => CommonUtil::buildImageUrl($giftDetails->deliveryGiftKind->image),
                            'count' => $giftDetails->count,
                            'userIdentity' => $userIdentity,
                            'userId' => $receiveUser->userId,
                            'prettyId' => $receiveUserModel != null ? $receiveUserModel->prettyId : $receiveUser->userId,
                            'userLevel' => $receiveUserModel != null ? $receiveUserModel->lvDengji : 0,
                            'nickName' => $receiveUserModel != null ? $receiveUserModel->nickname : '',
                            'isVip' => $receiveUserModel != null ? $receiveUserModel->vipLevel : 0,
                        ];
                        RoomNotifyService::getInstance()->notifyRoomMsg($roomId, [
                            'msg' => json_encode($msg2083),
                            'roomId' => 0,
                            'toUserId' => '0'
                        ]);
                    }
                }

                $msg2082 = [
                    'msgId' => 2082,
                    'boxCount' => $count,
                    'items' => $items,
                    'user' => [
                        'userIdentity' => $userIdentity,
                        'userId' => $receiveUser->userId,
                        'prettyId' => $receiveUserModel != null ? $receiveUserModel->prettyId : $receiveUser->userId,
                        'userLevel' => $receiveUserModel != null ? $receiveUserModel->lvDengji : 0,
                        'nickName' => $receiveUserModel != null ? $receiveUserModel->nickname : '',
                        'isVip' => $receiveUserModel != null ? $receiveUserModel->vipLevel : 0,
                        'avatar' => $receiveUserModel != null ? CommonUtil::buildImageUrl($receiveUserModel->avatar) : '',
                        'dukeLevel' => $receiveUserModel != null ? $receiveUserModel->dukeLevel : 0,
                    ]
                ];
                RoomNotifyService::getInstance()->notifyRoomMsg($roomId, [
                    'msg' => json_encode($msg2082),
                    'roomId' => $roomId,
                    'userId' => '0',
                ]);

                //发送全服公屏
                if (!empty($allRoomDatas)) {
                    $msg2082['items'] = $allRoomDatas;
                    RoomNotifyService::getInstance()->notifyRoomMsg($roomId, [
                        'msg' => json_encode($msg2082),
                        'roomId' => 0,
                        'toUserId' => '0',
                        'fromRoomId' => (int)$roomId
                    ]);
                }
            }
        } else {
            //判断大礼物全服飘屏
            if ($giftCoin >= $siteConf['send_gift_num'] || in_array($giftKind->kindId, [392, 393, 394, 443, 444])) {
                $msg2030['msgId'] = 2054;
                RoomNotifyService::getInstance()->notifyRoomMsg($roomId, [
                    'msg' => json_encode($msg2030),
                    'roomId' => 0,
                    'toUserId' => '0'
                ]);
            }
        }
        Log::info(sprintf('RoomNotifyService::notifySendGift roomId=%d fromUserId=%d giftId=%d count=%d',
            $roomModel->roomId, $fromUserId, $giftKind->kindId, $count));
    }

//    public function notifyMicCharm($roomId, $fromUserId, $giftKind, $count, $sendDetails, $receiveDetails) {
////        if(count($receiveUsers) == 1 and $receiveUsers[0]->userId == $receiveUsers[0]->micId){
////            $receiveUsers[0]->micId = 999;
////        }
////
////        $charmDatas = [];
////        foreach ($receiveUsers as $receiveUser) {
////            $charmDatas[(int)$receiveUser->micId] = $giftKind->deliveryCharm*$count;
////        }
////
////        CharmService::getInstance()->addCharm($roomId, $charmDatas);
//    }

    public function notifyDukeLevelChange($userId, $userModel, $roomId, $dukeLevel, $notifyType)
    {
        $dukeLevelConf = DukeSystem::getInstance()->findDukeLevel($dukeLevel);
        if ($dukeLevelConf) {
            $msg = [
                'msg' => json_encode([
                    'msgId' => 2086,
                    'dukeSvga' => CommonUtil::buildImageUrl($dukeLevelConf->animation),
                    'showType' => $notifyType,
                    'roomId' => $notifyType == 1 ? $roomId : 0,
                    'user' => [
                        'userId' => $userId,
                        'prettyId' => $userModel->prettyId,
                        'userLevel' => $userModel->lvDengji,
                        'nickName' => $userModel->nickname,
                        'isVip' => $userModel->vipLevel,
                        'dukeId' => $dukeLevel,
                    ]
                ]),
                'roomId' => $notifyType == 1 ? $roomId : 0,
                'toUserId' => '0'
            ];

            $socketUrl = config('config.socket_url');
            $msgData = json_encode($msg);
            //queue notify
            $resMsg = NotifyMessage::getInstance()->notify(['url' => $socketUrl, 'data' => $msgData, 'method' => 'POST', 'type' => 'json']);
            Log::info(sprintf('RoomNotifyService::notifyDukeLevelChange userId=%d roomId=%d socketUrl=%s sendData=%s resMsg=%s',
                $userId, $roomId, $socketUrl, $msgData, $resMsg));
        }
    }

    public function notifyBuyVip($event)
    {
        $userInfo = UserModelDao::getInstance()->loadUserModel($event->userId);
        $tmpShowType = $event->vipLevel == 2 ? 0 : 1;// 0飘屏 1公屏
        $msg = [
            'msg' => json_encode([
                'msgId' => 2087,
                'items' => [
                    'userId' => $userInfo->userId,
                    'prettyId' => $userInfo->prettyId,
                    'userLevel' => $userInfo->lvDengji,
                    'nickName' => $userInfo->nickname,
                    'avatar' => CommonUtil::buildImageUrl($userInfo->avatar),
                    'isVip' => $userInfo->vipLevel,
                    'dukeId' => $userInfo->dukeLevel,
                    'vipStatus' => $event->vipLevel,
                    "vipType" => $event->isOpen == true ? 2 : 1,   //1续费 2开通激活
                    'showType' => $tmpShowType,
                ]
            ]),
            'roomId' => 0,
            'toUserId' => '0'
        ];
        $socket_url = config('config.socket_url');
        $msgDataFull = json_encode($msg);

        //queue notify
        $resMsg = NotifyMessage::getInstance()->notify(['url' => $socket_url, 'data' => $msgDataFull, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('RoomNotifyService::notifyBuyVip userId=%d orderId=%d count=%s expiresTime=%s vipLevel=%s resMsg=%s',
            $event->userId, $event->orderId, $event->count, $event->expiresTime, $event->vipLevel, $resMsg));
    }


    /**
     * @info 2031 热度值变更
     * @param $newHot
     * @param $roomId
     * @return string
     */
    public function notifyHotChange($newHot, $roomId)
    {
        $strNum = ['msgId' => 2031, 'VisitorNum' => $newHot];
        $msg1['msg'] = json_encode($strNum);
        $msg1['roomId'] = (int)$roomId;
        $msg1['toUserId'] = '0';
        $socket_url = config('config.socket_url');
        $msgDataFull = json_encode($msg1);
        $msgId = NotifyMessage::getInstance()->notify(['url' => $socket_url, 'data' => $msgDataFull, 'method' => 'POST', 'type' => 'json']);
        Log::info(sprintf('RoomNotifyService::notifyHotChange success exec roomId:%d pushParam:%s pushRe:%s', $roomId, $msgDataFull, $msgId));
        return $msgId;
    }
}