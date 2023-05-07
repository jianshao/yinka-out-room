<?php


namespace app\domain\rank\event\handler;

use app\common\RedisCommon;
use app\domain\activity\confessionLove\ConfessionLoveService;
use app\domain\activity\sweet\SweetJourneyService;
use app\domain\asset\AssetKindIds;
use app\domain\rank\service\RankService;
use app\service\RoomNotifyService;
use think\facade\Log;
use Exception;

class RankEventHandler
{
    // 用户送礼增加魅力值
    public function onSendGiftEvent($event) {
        try{
            //$event->giftKind->price //$event->calcSenderConsumeAssetCount(AssetKindIds::$BEAN);
            $beanValue = 0;
            if ($event->giftKind->price && $event->giftKind->price->assetId == AssetKindIds::$BEAN && $event->giftKind->kindId != 395) {
                $beanValue = intval($event->giftKind->price->count * $event->count * count($event->receiveUsers));
            }

            if ($event->giftKind->isBox()) {
                $beanValue = 0;
                foreach ($event->receiveDetails as list($receiveUser, $giftDetails)) {
                    foreach ($giftDetails as $giftDetail) {
                        $beanValue += abs($giftDetail->deliveryGiftKind->deliveryCharm * $giftDetail->count);
                    }
                }
            }

            Log::info(sprintf('RankEventHandler::onSendGiftEvent userId=%d $beanValue=%d, giftcharm=%d',
                $event->fromUserId, $beanValue, $event->giftKind->deliveryCharm*$event->count));

            if ($beanValue > 0) {
                RankService::getInstance()->onRankRickChange($event->fromUserId, $event->roomId, $beanValue, $event->timestamp);
            }

            foreach ($event->receiveDetails as list($receiveUser, $giftDetails)) {
                $charmValue = 0;
                foreach ($giftDetails as $giftDetail) {
                    $charmValue += abs($giftDetail->deliveryGiftKind->deliveryCharm * $giftDetail->count);
                }
                RankService::getInstance()->onRankLikeChange([$receiveUser->userId], $event->roomId, $charmValue, $event->timestamp);
                RankService::getInstance()->onRankFansDevoteChange($event->fromUserId, [$receiveUser->userId], $event->giftKind->price->count * $event->count, $event->timestamp);
            }

            RoomNotifyService::getInstance()->notifySyncUserData($event->fromUserId);

            $redis = RedisCommon::getInstance()->getRedis();
            $activityConfig = $redis->get('520_activity_config');
            if (!empty($activityConfig)) {
                $config = json_decode($activityConfig, true);
                if($event->timestamp >= $config['start_time'] && $event->timestamp <= $config['end_time']) {
                    ConfessionLoveService::getInstance()->generateRankList($event, $config);
                }
            }
        }catch (Exception $e) {
            Log::error(sprintf('RankEventHandler::onSendGiftEvent Exception userId=%d ex=%d:%s file=%s:%d',
                $event->fromUserId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}