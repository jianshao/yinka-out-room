<?php


namespace app\event\handler;


use app\common\GetuiCommon;
use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\room\dao\RoomHotValueDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\event\SendGiftEvent;
use app\event\SendRedPacketEvent;
use app\service\RoomNotifyService;
use app\utils\CommonUtil;
use think\facade\Log;

class RoomHandler
{
    /**
     * @param $event SendRedPacketEvent
     * @return bool
     */
    public function onSendRedPacketEvent($event) {

        $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($event->roomId, $event->userId);

        //发消息
        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        $roomModel = RoomModelDao::getInstance()->loadRoom($event->roomId);
        $msg['msgId'] = 2080;
        $msg['roomId'] = $event->roomId;
        $msg['userIdentity'] = $userIdentity;
        $msg['prettyId'] = $userModel->prettyId;
        $msg['userId'] = $userModel->userId;
        $msg['userLevel'] = $userModel->lvDengji;
        $msg['nickName'] = $userModel->nickname;
        $msg['roomName'] = $roomModel->name;
        $msg['redPacketId'] = $event->redPacketId;
        $msg['imageUrl'] = CommonUtil::buildImageUrl('/image/testtxk/ic_red_envelope.png');
        $msg['isVip'] = $userModel->vipLevel;
        $msg['attires'] = [];

        $roomId = $event->roomId;
        if ($event->totalBean == 1456) {
            $msg['showType'] = 2;
            $roomId = 0;
        } elseif ($event->totalBean == 4536) {
            $msg['showType'] = 3;
            $roomId = 0;
        } else {
            $msg['showType'] = 1;
        }

        $msg['showTime'] = 10;
        $msgFull['msg'] = json_encode($msg);
        // $msgFull['roomId'] = 0;
        $msgFull['toUserId'] = '0';
        RoomNotifyService::getInstance()->notifyRoomMsg($roomId, [
            'msg' => json_encode($msgFull),
            'toUserId' => 0,
            'roomId' => $roomId
        ]);

        //添加热度
//        $redu = RoomHotValueDao::getInstance()->incHotValue($event->roomId, $roomModel->guildId, $event->totalBean*100);

        //发送消息
//        $strNum = ['msgId'=>2031,'VisitorNum'=>formatNumber($redu/100)];
//        $msg1['msg'] = json_encode($strNum);
//        $msg1['roomId'] = (int)$event->roomId;
//        $msg1['toUserId'] = '0';
//        RoomNotifyService::getInstance()->notifyRoomMsg($roomId, [
//            'msg' => json_encode($msg1),
//            'toUserId' => 0,
//            'roomId' => $roomId
//        ]);

        if ($event->totalBean == 4536) {
            $arrTmp = RedisCommon::getInstance()->getRedis()->HGETALL('user_current_room');
            $arrTmp = array_keys($arrTmp);
            if (!empty($arrTmp)) {
                $uidArr = array_chunk($arrTmp, 800);
                foreach($uidArr as $k=>$v) {
                    $result = GetuiCommon::getInstance()->pushMessageToList($v,0,$msgFull['msg']);
                    Log::info(sprintf('onSendRedPacketEvent userId=%d result=%s',
                        $event->userId, json_encode($result)));
                }
            }
        }

        return true;
    }
}

