<?php

namespace app\domain\led;

use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\game\box2\Box2System;
use app\domain\game\turntable\TurntableSystem;
use app\domain\gift\GiftSystem;
use app\domain\redpacket\RedPacketSystem;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\event\BreakBoxNewEvent;
use app\event\BuyVipEvent;
use app\event\DukeLevelChangeEvent;
use app\event\OreExchangeEvent;
use app\event\SendGiftEvent;
use app\event\SendRedPacketEvent;
use app\event\TaoJinRewardEvent;
use app\event\TurntableEvent;
use app\service\RoomNotifyService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use think\facade\Log;

/**
 * 跑马灯服务接口
 */
class LedService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LedService();
        }
        return self::$instance;
    }

    public function jumpKey(){
        return 'level_jump_conf';
    }

    /**
     * @param array $ledMsg led信息
     * @param int $roomId 发给房间
     * @param int $fromRoomId 过滤房间
     * @param int $fromUserId 过滤人
     */
    public function sendLed($ledMsg, $roomId=0, $fromRoomId=0, $fromUserId=0){
        $msg['msg'] = json_encode($ledMsg);
        $msg['roomId'] = (int)$roomId;
        $msg['fromRoomId'] = (int)$fromRoomId;
        $msg['fromUserId'] = (int)$fromUserId;
        Log::info(sprintf('LedService::sendLed msg=%s', json_encode($msg)));

        RoomNotifyService::getInstance()->notifyRoomLedMsg($roomId, $msg);
    }

    /**
     * 组装送礼led消息
     * @param $event SendGiftEvent
     */
    public function buildSendGifLedMsg($event){
        # 上5000豆；中2000豆
        $checkTopGiftValue = 5000; #大于这个值顶部
        $checkMiddleGiftValue = 2000; #大于这个值中部

        $type = "sendgift";
        $action = [
            "type" => "room",
            "roomId" => $event->roomId
        ];

        $sendUserModel = UserModelDao::getInstance()->loadUserModel($event->fromUserId);

        #送礼四部分组成 1start送礼人信息 2receiveUser收礼人信息 3Middle礼物信息 4end语
        $start = $this->encodeUserFormat($sendUserModel->avatar, $sendUserModel->nickname, null, "向");

        $end = $this->encodeNormalFormat("快来围观吧～");

        $topRichTexts = [];
        $middleRichTexts = [];
        foreach ($event->receiveDetails as $item) {
            $receiveUser = $item[0];
            $userModel = UserModelDao::getInstance()->loadUserModel($receiveUser->userId);
            $receiveUser = $this->encodeUserFormat($userModel->avatar, $userModel->nickname, null, "赠送了");

            $topMiddle = [];
            $middleMiddle = [];
            foreach ($item[1] as $giftDetails) {
                $giftItem = $this->encodeGiftFormat($giftDetails->deliveryGiftKind->name, $giftDetails->deliveryGiftKind->image, $giftDetails->count);

                $price = $giftDetails->deliveryGiftKind->price ? $giftDetails->deliveryGiftKind->price->count : 0;

                if ($price*$giftDetails->count >= $checkTopGiftValue){
                    $topMiddle = array_merge($topMiddle, $giftItem);
                }
                if ($price*$giftDetails->count >= $checkMiddleGiftValue){
                    $middleMiddle = array_merge($middleMiddle, $giftItem);
                }
            }

            if (!empty($topMiddle)){
                $topRichTexts[] = array_merge($start, $receiveUser, $topMiddle, $end);
            }
            if (!empty($middleMiddle)){
                $middleRichTexts[] = array_merge($start, $receiveUser, $middleMiddle);
            }
        }

        Log::info(sprintf('LedService::buildSendGifLedMsg userId=%d topRichTexts=%s middleRichTexts=%s',
            $event->fromUserId, json_encode($topRichTexts), json_encode($middleRichTexts)));

        #顶部的led
        if (!empty($topRichTexts)){
            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$GIFTTOP);
            $ledMsg[] = [
                'background' => $ledKind->makeBackground(),
                'richTexts' => $topRichTexts,
                'type' => $type,
                'action' => $action,
                'location' => LedConst::$TOP,
            ];

            $this->sendLed($ledMsg);
        }

        #中部的led
//        if (!empty($middleRichTexts)){
//            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$GIFTMIDDLE);
//            $ledMsg[] = [
//                'background' => $ledKind->makeBackground(),
//                'richTexts' => $middleRichTexts,
//                'type' => $type,
//                'location' => LedConst::$MIDDLE,
//            ];
//
//            $this->sendLed($ledMsg);
//        }
    }

    /**
     * 组装转盘led消息
     * @param $event TurntableEvent
     */
    public function buildTurnTableLedMsg($event){
        $giftMap = $event->deliveryGiftMap;
        if (empty($giftMap)) {
            return;
        }

        #上3000豆；中1000豆
        $checkTopGiftValue = 5000; #大于这个值顶部
        $checkMiddleGiftValue = 2000; #大于这个值中部

        $box = TurntableSystem::getInstance()->findBox($event->boxId);

        $type = "turntable";
        $action = [
            "type" => "h5",
            "name" => $type
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);

        #三部分组成 1start玩游戏人信息 2礼物信息 3end语
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', sprintf("在%s转盘中获得了",$box->name));

        $topMiddle = [];
        $middleMiddle = [];
        foreach ($giftMap as $giftId => $count) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind) {
                $price = $giftKind->price ? $giftKind->price->count:0;
                $item = $this->encodeGiftFormat($giftKind->name, $giftKind->image, $count);

                if ($price >= $checkMiddleGiftValue){
                    $middleMiddle = array_merge($middleMiddle, $item);
                }

                if ($price >= $checkTopGiftValue){
                    $topMiddle = array_merge($topMiddle, $item);
                }
            }
        }

        $end = $this->encodeNormalFormat("运气爆棚呀～");

        #顶部的led
        if (!empty($topMiddle)){
            $richTexts[] = array_merge($start, $topMiddle, $end);

            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$TURNTABLETOP);
            $ledMsg[] = [
                'background' => $ledKind->makeBackground(),
                'richTexts' => $richTexts,
                'type' => $type,
                'action' => $action,
                'location' => LedConst::$TOP,
            ];

            $this->sendLed($ledMsg, 0, 0, $event->userId);
        }

//        #中部的led
//        if (!empty($middleMiddle)){
//            $richTexts[] = array_merge($start, $middleMiddle, $end);
//            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$TURNTABLEMIDDLE);
//            $ledMsg[] = [
//                'background' => $ledKind->makeBackground(),
//                'richTexts' => $richTexts,
//                'type' => $type,
//                'location' => LedConst::$MIDDLE,
//            ];
//
//            $this->sendLed($ledMsg);
//        }
    }

    /**
     * 组装宝箱led消息
     * @param $event BreakBoxNewEvent
     */
    public function buildBoxLedMsg($event){
        $giftMap = $event->deliveryGiftMap;
        if (empty($giftMap)) {
            return;
        }

        #上3000豆；中1000豆
        $checkTopGiftValue = 5000; #大于这个值顶部
        $checkMiddleGiftValue = 2000; #大于这个值中部

        $box = Box2System::getInstance()->findBox($event->boxId);

        $type = "box";
        $action = [
            "type" => "h5",
            "name" => $type
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        #三部分组成 1start玩游戏人信息 2礼物信息 3end语
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', sprintf("在%s宝箱中获得了",$box->name));

        $topMiddle = [];
        $middleMiddle = [];
        foreach ($giftMap as $giftId => $count) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind) {
                $price = $giftKind->price ? $giftKind->price->count:0;
                $item = $this->encodeGiftFormat($giftKind->name, $giftKind->image, $count);

                if ($price >= $checkMiddleGiftValue){
                    $middleMiddle = array_merge($middleMiddle, $item);
                }

                if ($price >= $checkTopGiftValue){
                    $topMiddle = array_merge($topMiddle, $item);
                }
            }
        }

        $end = $this->encodeNormalFormat("运气爆棚呀～");

        #顶部的led
        if (!empty($topMiddle)){
            $richTexts[] = array_merge($start, $topMiddle, $end);

            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$BOXTOP);
            $ledMsg[] = [
                'background' => $ledKind->makeBackground(),
                'richTexts' => $richTexts,
                'type' => $type,
                'action' => $action,
                'location' => LedConst::$TOP,
            ];

            $this->sendLed($ledMsg);
        }

        #中部的led
//        if (!empty($middleMiddle)){
//            $richTexts[] = array_merge($start, $middleMiddle, $end);
//            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$BOXMIDDLE);
//            $ledMsg[] = [
//                'background' => $ledKind->makeBackground(),
//                'richTexts' => $richTexts,
//                'type' => $type,
//                'location' => LedConst::$MIDDLE,
//            ];
//
//            $this->sendLed($ledMsg);
//        }
    }

    /**
     * 组装淘金led消息
     * @param $event TaoJinRewardEvent
     */
    public function buildTaoJingLedMsg($event){
        $type = "taojing";
        $action = [
            "type" => "h5",
            "name" => $type
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);

        #三部分组成 1start玩游戏人信息 2middle奖励信息 3end语
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', "在淘金之旅中获得了");
        $end = $this->encodeNormalFormat("快来围观吧～");

        $richTexts = null;
        foreach ($event->rewards as list($diceNum, $taojinReward)){
            if ($taojinReward->reward->assetId != AssetKindIds::$BEAN || $taojinReward->reward->count < 2000){
                continue;
            }
            $middle = $this->encodeNormalFormat($taojinReward->reward->count.'豆', "bold", "#FFFFFF");

            $richTexts[] = array_merge($start, $middle, $end);
        }

        if (!empty($richTexts)){

            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$TAOJING);
            $ledMsg[] = [
                'background' => $ledKind->makeBackground(),
                'richTexts' => $richTexts,
                'type' => $type,
                'action' => $action,
                'location' => LedConst::$TOP,
            ];

            $this->sendLed($ledMsg);
        }
    }

    /**
     * 组装淘金兑换led消息
     * @param $event OreExchangeEvent
     */
    public function buildOreExchangeLedMsg($event){
        Log::info(sprintf('LedService::onOreExchangeEvent userId=%d assetId=%s',
            $event->userId, $event->assetId));

        //兑换海洋之心发公屏消息，爱的巨轮、火箭、烟花城堡均发飘屏消息
        if(!in_array(AssetUtils::getGiftKindIdFromAssetId($event->assetId), config('config.exchange_gift.float_screen')))
        {
            return;
        }

        $type = "taojing";
        $action = [
            "type" => "h5",
            "name" => $type
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        $asset = AssetSystem::getInstance()->findAssetKind($event->assetId);
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', "在淘金之旅中兑换了");
        $middle = $this->encodeGiftFormat($asset->displayName, $asset->image, 1);
        $end = $this->encodeNormalFormat("快来围观吧～");

        $richTexts[] = array_merge($start, $middle, $end);
        $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$TAOJING);
        $ledMsg[] = [
            'background' => $ledKind->makeBackground(),
            'richTexts' => $richTexts,
            'type' => $type,
            'action' => $action,
            'location' => LedConst::$TOP,
        ];

        $this->sendLed($ledMsg);
    }

    /**
     * 组装爵位升级led消息
     * @param $event DukeLevelChangeEvent
     */
    public function buildDukeLevelChangeEvent($event){
        if ($event->roomId == 0 || in_array($event->newDukeLevel, [1, 2, 3])) {
            return;
        }

        $roomName = RoomModelDao::getInstance()->getRoomName($event->roomId);

        $type = "duke";
        $action = [
            "type" => "room",
            "roomId" => $event->roomId
        ];
        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', sprintf("在%s爵位升级至", $roomName));
        $middle = $this->encodeNormalFormat($event->newDukeLevel==5?"国王":"公爵", "bold", "#FFFFFF");
        $end = $this->encodeNormalFormat("大佬666～");

        $richTexts[] = array_merge($start, $middle, $end);
        $ledKind = LedSystem::getInstance()->getLedKind($event->newDukeLevel==5?LedConst::$DUKEKING:LedConst::$DUKE);
        $ledMsg[] = [
            'background' => $ledKind->makeBackground(),
            'richTexts' => $richTexts,
            'type' => $type,
            'action' => $action,
            'location' => LedConst::$TOP,
        ];

        $this->sendLed($ledMsg);
    }

    /**
     * 组装svip续费led消息
     * @param $event BuyVipEvent
     */
    public function buildBuyVipEvent($event){
//        $event->isOpen == true ? 2 : 1,   //1续费 2开通激活
        if ($event->vipLevel != 2 || $event->isOpen == true) {
            return;
        }

        $type = "svip";
        $action = [
            "type" => "user",
            "userId" => $event->userId
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', "续费");
        $middle = $this->encodeNormalFormat("SVIP", "bold", "#FFFFFF");
        $end = $this->encodeNormalFormat("大佬666～");

        $richTexts[] = array_merge($start, $middle, $end);
        $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$SVIP);
        $ledMsg[] = [
            'background' => $ledKind->makeBackground(),
            'richTexts' => $richTexts,
            'type' => $type,
            'action' => $action,
            'location' => LedConst::$TOP,
        ];

        $this->sendLed($ledMsg);
    }

    /**
     * 组装发红包led消息
     * @param $event SendRedPacketEvent
     */
    public function buildSendRedPacketEvent($event){
        $redPacket = RedPacketSystem::getInstance()->findRedPacketByBean($event->totalBean);
        if (empty($redPacket) || $redPacket->showType == 1){
            #小红包不飘屏
            return;
        }

        $roomName = RoomModelDao::getInstance()->getRoomName($event->roomId);

        $type = "red";
        $action = [
            "type" => "room",
            "roomId" => $event->roomId
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,null, "在");
        $middle = $this->encodeNormalFormat($roomName, "bold", "#FFFFFF");
        $end = $this->encodeNormalFormat("发红包啦！快去抢红包！");

        $richTexts[] = array_merge($start, $middle, $end);

        $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$HONGBAO);
        $ledMsg[] = [
            'background' => $ledKind->makeBackground(),
            'richTexts' => $richTexts,
            'type' => $type,
            'action' => $action,
            'location' => LedConst::$TOP,
        ];

        $this->sendLed($ledMsg);
    }

    /**
     * 组装地鼠王led消息
     */
    public function sendGopherKingLedMsg($status){
        $type = "gopher";
        $action = [
            "type" => "h5",
            "name" => $type,
            'status' => $status
        ];

        $richTexts[] = $this->encodeNormalFormat("地鼠王出现啦，全军出击！ 剿灭地鼠王！", "bold", "#FFFFFF");
        $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$GOPHERKING);
        $ledMsg[] = [
            'background' => $ledKind->makeBackground(),
            'richTexts' => $richTexts,
            'type' => $type,
            'action' => $action,
            'location' => LedConst::$TOP,
        ];

        $this->sendLed($ledMsg);
    }

    /**
     * 组装打死地鼠王led消息
     */
    public function sendKOGopherKingLedMsg($userId, $roomId, $reward, $status){
        $type = "gopher";
        $action = [
            "type" => "h5",
            "name" => $type,
            'status' => $status
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', "剿灭地鼠王，获得");
        $middle = $this->encodeNormalFormat($reward, "bold", "#FFFFFF");
        $end = $this->encodeNormalFormat("积分");

        $richTexts[] = array_merge($start, $middle, $end);
        $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$KOGOPHERKING);
        $ledMsg[] = [
            'background' => $ledKind->makeBackground(),
            'richTexts' => $richTexts,
            'type' => $type,
            'action' => $action,
            'location' => LedConst::$TOP,
        ];

        $this->sendLed($ledMsg);
    }

    /**
     * 组装通用的游戏类似转盘的跑马灯
     * @param $event TurntableEvent
     */
    public function sendCommonLedMsg($userId, $gameName, $gameType, $results){

        $type = $gameType;
        $action = [
            "type" => "h5",
            "name" => $type
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);

        #三部分组成 1start玩游戏人信息 2礼物信息 3end语
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', sprintf("在%s中获得了",$gameName));

        $topMiddle = [];
        foreach ($results as $key => $info) {
            $giftId = $info['giftId'];
            $count = $info['count'];
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind) {
                $item = $this->encodeGiftFormat($info['giftName'], $info['giftImage'], $count);

                $topMiddle = array_merge($topMiddle, $item);
            }
        }

        $end = $this->encodeNormalFormat("运气爆棚呀～");

        #顶部的led
        if (!empty($topMiddle)){
            $richTexts[] = array_merge($start, $topMiddle, $end);

            $ledKind = LedSystem::getInstance()->getLedKind($gameType);
            $ledKind = $ledKind ? $ledKind : LedSystem::getInstance()->getLedKind(LedConst::$COMMON);
            $ledMsg[] = [
                'background' => $ledKind->makeBackground(),
                'richTexts' => $richTexts,
                'type' => $type,
                'action' => $action,
                'location' => LedConst::$TOP,
            ];

            $this->sendLed($ledMsg);
        }

//        #中部的led
//        if (!empty($middleMiddle)){
//            $richTexts[] = array_merge($start, $middleMiddle, $end);
//            $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$TURNTABLEMIDDLE);
//            $ledMsg[] = [
//                'background' => $ledKind->makeBackground(),
//                'richTexts' => $richTexts,
//                'type' => $type,
//                'location' => LedConst::$MIDDLE,
//            ];
//
//            $this->sendLed($ledMsg);
//        }
    }

    /**
     * 组装兑换金条续费消息
     * @param $event BuyVipEvent
     */
    public function buildGoldBarEvent($userId){
        $type = "goldBar";
        $action = [
            "type" => "user",
            "userId" => $userId
        ];

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        $start = $this->encodeUserFormat($userModel->avatar, $userModel->nickname,'恭喜', "在小音送福活动中获得了10g金条*1");

        $richTexts[] = $start;
        $ledKind = LedSystem::getInstance()->getLedKind(LedConst::$COMMON);
        $ledMsg[] = [
            'background' => $ledKind->makeBackground(),
            'richTexts' => $richTexts,
            'type' => $type,
            'action' => $action,
            'location' => LedConst::$TOP,
        ];

        $this->sendLed($ledMsg);
    }

    /**
     * @param string $avatar 用户图像
     * @param string $nickname 用户名称
     * @param null $startStr 该用户格式前语 如：恭喜***获得 恭喜是startStr
     * @param null $endStr 该用户格式前语 如：恭喜***获得 获得是endstr
     * @return array[]
     */
    private function encodeUserFormat($avatar, $nickname, $startStr=null, $endStr=null){
        $user = [
            [
                "type" => "image",
                "content" => CommonUtil::buildImageUrl($avatar)
            ],
            [
                "type" => "text",
                "content" => $nickname,
                "fontWeight" => "bold",
                "color" => "#FFFFFF"
            ]
        ];

        $start = [];
        if (!empty($startStr)){
            $start = [
                [
                    "type" => "text",
                    "content" => $startStr,
                    "fontWeight" => "normal",
                    "color" => "#66FFFFFF"
                ]
            ];
        }

        $end = [];
        if (!empty($endStr)){
            $end = [
                [
                    "type" => "text",
                    "content" => $endStr,
                    "fontWeight" => "normal",
                    "color" => "#66FFFFFF"
                ]
            ];
        }
        return array_merge($start, $user, $end);
    }

    private function encodeGiftFormat($giftName, $giftImage, $count){
        return [
            [
                "type" => "image",
                "content" => CommonUtil::buildImageUrl($giftImage)
            ],
            [
                "type" => "text",
                "content" => sprintf('[%s]*%d', $giftName, $count),
                "fontWeight" => "bold",
                "color" => "#FFAB00"
            ]
        ];
    }

    private function encodeNormalFormat($strMsg, $fontWeight='normal', $color='#FFFFFFFF'){
        return [
            [
                "type" => "text",
                "content" => $strMsg,
                "fontWeight" => $fontWeight,
                "color" => $color
            ]
        ];
    }
}