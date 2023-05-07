<?php

namespace app\domain\gift\event\handler;

use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\gift\dao\GiftWallModelDao;
use app\domain\gift\event\OpenGiftDomainEvent;
use app\domain\gift\event\ReceiveGiftDomainEvent;
use app\domain\gift\service\GiftDetails;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\user\dao\UserModelDao;
use app\service\RoomNotifyService;
use app\utils\CommonUtil;
use Exception;
use think\facade\Log;

class GiftEventHandler
{
    /**
     * @param $event ReceiveGiftDomainEvent
     * */
    public function onReceiveGiftDomainEvent($event) {
        try{
            foreach ($event->giftDetailsList as $detail) {
                Log::info(sprintf('GiftEventHandler::onReceiveGiftDomainEvent userId=%d kindId=%d:%d',
                    $event->receiveUser->userId, $detail->deliveryGiftKind->kindId, $detail->count));

                GiftWallModelDao::getInstance()->incGift($event->receiveUser->userId,
                    $detail->deliveryGiftKind->kindId,
                    $detail->count);
            }

            # 盲盒锦鲤榜
            $redis = RedisCommon::getInstance()->getRedis();
            foreach ($event->giftDetailsList as $giftDetails) {
                assert($giftDetails instanceof GiftDetails);

                if ($giftDetails->giftKind->isBox()
                    && $giftDetails->deliveryGiftKind->price != null
                    && $giftDetails->deliveryGiftKind->price->assetId == AssetKindIds::$BEAN) {

                    if ($giftDetails->deliveryGiftKind->price->count >= 300){
                        // 锦鲤
                        $jinliRankKey = 'rank_giftbox_jinli';
                        $redis->lPush($jinliRankKey, json_encode([
                            'userId' => $event->user->getUserId(),
                            'fromUserId' => $event->fromUserId,
                            'boxGiftId' => $giftDetails->giftKind->kindId,
                            'giftId' => $giftDetails->deliveryGiftKind->kindId,
                            'count' => $giftDetails->count,
                            'time' => $event->timestamp
                        ]));

                        $redis->lTrim($jinliRankKey, 0, 200 - 1);
                    }

                    if ($giftDetails->deliveryGiftKind->price->count >= 100){
                        // 滚动
                        $jinliRankKey = 'rank_giftbox_scroll';
                        $redis->lPush($jinliRankKey, json_encode([
                            'userId' => $event->user->getUserId(),
                            'fromUserId' => $event->fromUserId,
                            'boxGiftId' => $giftDetails->giftKind->kindId,
                            'giftId' => $giftDetails->deliveryGiftKind->kindId,
                            'count' => $giftDetails->count,
                            'time' => $event->timestamp
                        ]));

                        $redis->lTrim($jinliRankKey, 0, 50 - 1);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error(sprintf('GiftEventHandler::onReceiveGiftDomainEvent error userId=%d ex=%d:%s',
                    $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @param $event OpenGiftDomainEvent
     * */
    public function onOpenGiftDomainEvent($event) {
        try {
            $socketFullMsg = [];
            $userModel = UserModelDao::getInstance()->loadUserModel($event->user->getUserId());
            $userIdentity = RoomManagerModelDao::getInstance()->viewUserIdentity($event->roomId, $event->user->getUserId());
            $redis = RedisCommon::getInstance()->getRedis(["select" => 3]);
            $luckyBagMarqueeValue = $redis->hGet('public_screen_conf', 'lucky_bag_marquee_value');
            $luckyBagPublicScreenValue = $redis->hGet('public_screen_conf', 'lucky_bag_public_screen_value');
            foreach ($event->gainAssets as $assetItem){
                $asset = AssetSystem::getInstance()->findAssetKind($assetItem->assetId);

                if ($assetItem->count >= $luckyBagMarqueeValue){
                    $socketFullMsg[] = ['content'=>'恭喜'.$userModel->nickname.'用户在'.$event->giftKind->name.'开出'.$assetItem->count.$asset->displayName];
                }
                if($assetItem->count >= $luckyBagPublicScreenValue){
                    #公屏消息
                    $publicScreenMsg = [
                        'msgId'=>2092,
                        'items'=>[
                            'showType' => 1,
                            'roomId' => $event->roomId,
                            'userId' => $userModel->userId,
                            'prettyId' => $userModel->prettyId,
                            'name' => $userModel->nickname,
                            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                            'userLevel' => $userModel->lvDengji,
                            'isVip' => $userModel->vipLevel,
                            'dukeLevel' => $userModel->dukeLevel,
                            'userIdentity' => $userIdentity,
                            'giftId' => $event->giftKind->kindId,
                            'giftName' => $event->giftKind->name,
                            'giftAnimation' => CommonUtil::buildImageUrl($event->giftKind->giftAnimation),
                            'giftImage' => CommonUtil::buildImageUrl($event->giftKind->image),
                            'classType' => $event->giftKind->classType,
                            'gainName' => $asset->displayName,
                            'gainImage' => CommonUtil::buildImageUrl($asset->image),
                            'gainCount' => $assetItem->count
                        ],
                    ];
                    $msgfull['msg'] = json_encode($publicScreenMsg);
                    $msgfull['roomId'] = 0;
                    $msgfull['toUserId'] = '0';
                    $resfull = RoomNotifyService::getInstance()->notifyRoomMsgLite($event->roomId, $msgfull);
                    Log::record("福袋游戏公屏状态-----". $resfull, "info" );
                }
            }

            if (count($socketFullMsg) > 0){
                //房间内跑马灯
                $strfull = [
                    'msgId'=>2085,
                    'items'=>$socketFullMsg,
                ];
                $msgfull['msg'] = json_encode($strfull);
                $msgfull['roomId'] = 0;
                $msgfull['toUserId'] = '0';
                $resfull = RoomNotifyService::getInstance()->notifyRoomMsgLite($event->roomId, $msgfull);
                Log::record("福袋游戏全服发送状态-----". $resfull, "info" );
            }
        } catch (Exception $e) {
            Log::error(sprintf('GiftEventHandler::onOpenGiftDomainEvent userId=%d ex=%d:%s',
                $event->user->getUserId(), $e->getCode(), $e->getMessage()));
        }
    }

}