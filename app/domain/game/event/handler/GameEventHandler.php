<?php


namespace app\domain\game\event\handler;


use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\game\taojin\dao\EnergyDao;
use app\domain\game\turntable\TurntableService;
use app\domain\user\dao\UserModelDao;
use app\event\TaoJinRewardEvent;
use app\utils\CommonUtil;
use Exception;
use think\facade\Log;

class GameEventHandler
{

    // 用户送礼增加体力
//    public function onSendGiftEvent($event) {
//        try{
//            $beanValue = $event->giftKind->getPriceByAssetId(AssetKindIds::$BEAN) * $event->count * count($event->receiveUsers);
//            Log::info(sprintf('GameEventHandler::onSendGiftEvent userId=%d beanValue=%d',
//                $event->fromUserId, $beanValue));
//            if ($beanValue > 0) {
//                //获得礼物价值的50%的体力值
//                $energy = floor($beanValue/2);
//                EnergyDao::getInstance()->incEnergy($event->fromUserId, $energy);
//            }
//        }catch (Exception $e) {
//            Log::error(sprintf('GameEventHandler::onSendGiftEvent $userId=%d ex=%d:%s file=%s:%d',
//                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
//        }
//
//    }

    // 矿石兑换
    public function onOreExchangeEvent($event) {
        try{
            Log::info(sprintf('GameEventHandler::onOreExchangeEvent userId=%d assetId=%s',
                $event->userId, $event->assetId));

            //兑换海洋之心发公屏消息，爱的巨轮、火箭、烟花城堡均发飘屏消息
            if(in_array(AssetUtils::getGiftKindIdFromAssetId($event->assetId), config('config.exchange_gift.public_screen')))
            { //1公屏
                $tmpShowType = 1;
            } else { //2飘屏
                $tmpShowType = 0;
            }

            $asset = AssetSystem::getInstance()->findAssetKind($event->assetId);

            $userModel = UserModelDao::getInstance()->loadUserModel($event->userId);
            $strRoom = [
                'msgId'=>2088,
                'items'=> [
                    'userId'=>$userModel->userId,
                    'prettyId'=>$userModel->prettyId,
                    'userLevel'=>$userModel->lvDengji,
                    'nickName'=>$userModel->nickname,
                    'isVip'=>$userModel->vipLevel,
                    'dukeId'=>$userModel->dukeLevel,
                    'showType'=>$tmpShowType,
                    'gift_name' => $asset->displayName,
                    'gift_image' => CommonUtil::buildImageUrl($asset->image),
                    'gift_num' => 1
                ]
            ];
            $socket_url = config('config.socket_url');
            $msgFull['msg'] = json_encode($strRoom);
            $msgFull['roomId'] = 0;
            $msgFull['toUserId'] = '0';
            $msgDataFull = json_encode($msgFull);
            $resFull = curlData($socket_url, $msgDataFull , 'POST', 'json');
            Log::record("兑换海洋之心发公屏消息发送参数-----". $msgDataFull, "info" );
            Log::record("兑换海洋之心发公屏消息发送状态-----". $resFull, "info" );
        }catch (Exception $e) {
            Log::error(sprintf('GameEventHandler::onOreExchangeEvent $userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }

    }


    /**
     * @param $event TaoJinRewardEvent
     * */
    public function onTaoJinRewardEvent($event) {
        try {
            $socketFullMsg = [];
            $nickname = UserModelDao::getInstance()->findNicknameByUserId($event->userId);
            foreach ($event->rewards as list($diceNum, $taojinReward)){
                if ($taojinReward->reward->assetId != AssetKindIds::$BEAN || $taojinReward->reward->count < 2000){
                    continue;
                }

                $socketFullMsg[] = ['content'=>'恭喜'.$nickname.'用户在淘金之旅活动中获得了'.$taojinReward->reward->count.'豆'];
            }

            //房间内跑马灯
            if (count($socketFullMsg) > 0){
                $strfull = [
                    'msgId'=>2085,
                    'items'=>$socketFullMsg,
                ];
                $socket_url = config('config.socket_url');
                $msgfull['msg'] = json_encode($strfull);
                $msgfull['roomId'] = 0;
                $msgfull['toUserId'] = '0';
                $msgDatafull = json_encode($msgfull);
                $resfull = curlData($socket_url, $msgDatafull , 'POST', 'json');
                Log::record("淘金之旅游戏全服发送参数-----". $msgDatafull, "info" );
                Log::record("淘金之旅游戏全服发送状态-----". $resfull, "info" );
            }
        } catch (Exception $e) {
            Log::error(sprintf('GameEventHandler::onTaoJinRewardEvent userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    public function onTurntableEvent($event) {
        try {
            Log::info(sprintf('GameEventHandler::onTurntableEvent event=%s', json_encode($event)));
            TurntableService::getInstance()->packageScreenMessage($event);
        } catch (Exception $e) {
            Log::error(sprintf('GameEventHandler::onTurntableEvent event=%s es=%s ex=%s',
                json_encode($event), $e->getMessage(), $e->getTraceAsString()));
        }
    }


}