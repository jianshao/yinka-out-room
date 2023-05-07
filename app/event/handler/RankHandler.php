<?php


namespace app\event\handler;
use app\common\RedisCommon;
use app\domain\gift\GiftSystem;
use think\facade\Log;
use Exception;


class RankHandler
{
    public function calcScoreByAssets($consumeAssetList) {
        $score = 0;
        foreach ($consumeAssetList as $key => $value) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($key);
            $score +=  $giftKind->price->count * $value;
        }
        return $score;
    }

    public function onBreakBoxEvent($event) {
        try {
            Log::info(sprintf('RankHandler::onBreakBoxEvent userId=%d', $event->userId));
            $week = weekMonday(false);
            $score = $this->calcScoreByAssets($event->deliveryGiftMap);
            $today = date('Ymd');
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->ZINCRBY('rank_box_fuxing_' . $today, $score, $event->userId);
            $redis->ZINCRBY('rank_box_fudi_' . $today, $score, $event->roomId);
        } catch (Exception $e) {
            Log::error(sprintf('onBreakBoxEvent::rankList userId=%d boxId=%s ex=%d:%s trace=%s',
                $event->userId, $event->boxId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }
}