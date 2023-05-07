<?php


namespace app\event\handler;


use app\domain\prop\PropKindBubble;
use app\domain\user\dao\UserModelDao;
use app\query\prop\service\PropQueryService;
use app\domain\redpacket\RedPacketSystem;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\query\user\QueryUserService;
use app\service\RoomHotService;
use app\service\RoomNotifyService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\facade\Log;
use Exception;

class RedPacketHandler
{
    public function onSendRedPacketEvent($event) {
        //发消息
        try {
            Log::debug(sprintf('RedPacketHandler::onSendRedPacketEvent roomId=%d redPacketId=%d totalBean=%d',
                $event->roomId, $event->redPacketId, $event->totalBean));

            $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($event->roomId, $event->userId);

            $bubble = PropQueryService::getInstance()->getWaredProp($event->userId, PropKindBubble::$TYPE_NAME);
            $queryUser = UserModelDao::getInstance()->loadUserModel($event->userId);

            $roomModel = RoomModelDao::getInstance()->loadRoom($event->roomId);
            Log::debug(sprintf('RedPacketHandler::onSendRedPacketEvent roomId=%d',
                $event->roomId));
            $redPacket = RedPacketSystem::getInstance()->findRedPacketByBean($event->totalBean);
            $showType = $redPacket != null ? $redPacket->showType : 1;

            $msg = [
                'msg' => json_encode([
                    'msgId' => 2080,
                    'roomId' => $event->roomId,
                    'roomName' => empty($roomModel) ? '' : $roomModel->name,
                    'userIdentity' => $userIdentity,
                    'prettyId' => $queryUser->prettyId,
                    'userId' => $event->userId,
                    'userLevel' => $queryUser->lvDengji,
                    'nickName' => $queryUser->nickname,
                    'redPacketId' => $event->redPacketId,
                    'imageUrl' => CommonUtil::buildImageUrl('image/testtxk/ic_red_envelope.png'),
                    'isVip' => $queryUser->vipLevel,
                    'attires' => $bubble != null ? [$bubble->kind->kindId] : null,
                    'bubble' => PropQueryService::getInstance()->encodeBubbleInfo($bubble),
                    'showType' => $showType,
                    'showTime' => 10,
                ]),
                'roomId' => $showType == 1 ? $event->roomId : 0,
                'toUserId' => '0'
            ];
            RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msg);

//            $guildId = ArrayUtil::safeGet($roomInfo, 'guild_id', 0);
//            $hotValue = RoomHotService::getInstance()->incrRoomHotByBeanValue($event->roomId, $guildId, $event->totalBean);

//            $msg = [
//                'msg' => json_encode([
//                    'msgId' => 2031,
//                    'VisitorNum' => formatNumber($hotValue / 100)
//                ]),
//                'roomId' => $event->roomId,
//                'toUserId' => '0'
//            ];
//            RoomNotifyService::getInstance()->notifyRoomMsg($event->roomId, $msg);
        } catch (Exception $e) {
            Log::error(sprintf('RedPacketHandler::onSendRedPacketEvent roomId=%d redPacketId=%d totalBean=%d ex=%d:%s trace=%s',
                $event->roomId, $event->redPacketId, $event->totalBean,
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }
}