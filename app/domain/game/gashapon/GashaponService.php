<?php

namespace app\domain\game\gashapon;

use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\game\poolbase\RunningRewardPool;
use app\event\DoLotteryEvent;
use think\facade\Log;


/**
 * 金币抽奖
 */
class GashaponService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GashaponService();
        }
        return self::$instance;
    }

    public function buildPoolKey() {
        return 'goshapon_run_pool';
    }

    public function buildPoolIdKey() {
        return 1;
    }

    public function ensurePoolExists() {
        $poolKey = $this->buildPoolKey();
        $idKey = $this->buildPoolIdKey();
        $poolConfStr = GashaponSystem::getInstance()->encodeToDaoRedisJson();
        list($ec, $curPoolStr) = RunningRewardPool::ensurePoolExists($poolKey, $idKey, $poolConfStr);
        Log::info(sprintf('GashaponService::ensurePoolExists poolConfStr=%s curPoolStr=%s', $poolConfStr, $curPoolStr));

        return $curPoolStr;
    }

    public function refreshPool() {
        $poolKey = $this->buildPoolKey();
        $idKey = $this->buildPoolIdKey();
        $poolConfStr = GashaponSystem::getInstance()->encodeToDaoRedisJson();
        list($ec, $curPoolStr) = RunningRewardPool::refreshPool($poolKey, $idKey, $poolConfStr);

        Log::info(sprintf('TurntableService::refreshRewardPool poolConfStr=%s curPoolStr=%s',
            $poolConfStr, $curPoolStr));
        return $curPoolStr;
    }

    public function doLottery($userId, $roomId, $count, $timestamp) {
        if (!in_array($count, GashaponSystem::getInstance()->counts)) {
            throw new FQException('抽奖次数错误', 500);
        }

        $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'gashapon', '', $count);

        // 收费
        list($totalPrice,$balance) = $this->collectFee($userId, $count, $biEvent, $timestamp);

        // 抽奖
        $resMap = $this->doRewardPool($userId, $roomId, $count);

        // 发奖
        $lotterys = $this->deliveryGifts($userId, $resMap, $biEvent, $timestamp);

        // 记录
        $this->processRank($userId, $lotterys, $timestamp);
        event( new DoLotteryEvent($userId,$totalPrice,$balance,$timestamp));

        return $resMap;
    }

    private function collectFee($userId, $count, $biEvent, $timestamp) {
        if ($count == 1){
            list($consume, $balance) = AssetUtils::consumeAsset($userId, AssetKindIds::$GAME_GASHAPON, 1, $timestamp, $biEvent);
            if ($consume >= 1) {
                // 扣费成功
                return [0,0];
            }
        }
        $price = GashaponSystem::getInstance()->price;
        $totalPrice = $price->count * $count;
        list($consume, $balance) = AssetUtils::consumeAsset($userId, $price->assetId, $totalPrice, $timestamp, $biEvent);
        if ($consume >= $totalPrice) {
            // 扣费成功
            return [$totalPrice,$balance];
        }

        throw new FQException('金币数量不足', 500);
    }

    private function doRewardPool($userId, $roomId, $count) {
        try {
            $poolKey = $this->buildPoolKey();
            $idKey = $this->buildPoolIdKey();
            $poolConfStr = GashaponSystem::getInstance()->encodeToDaoRedisJson();

            $this->ensurePoolExists();

            list($resMap, $breakReGiftId) = RunningRewardPool::breakGift($poolKey, $idKey, $count, $poolConfStr, null);
            Log::info(sprintf('GashaponService::doTurnRewardPool userId=%d, roomId=%d count=%d, resMap=%s',
                $userId, $roomId, $count, json_encode($resMap)));

            return $resMap;
        } catch (\Exception $e) {
            Log::error(sprintf('GashaponService::doTurnRewardPool userId=%d roomId=%d ex=%d:%s trace=%s',
                $userId, $roomId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

    private function deliveryGifts($userId, $resMap, $biEvent, $timestamp) {
        $deliveryGiftMap = [];
        foreach ($resMap as $lotteryId=> $count) {
            $deliveryGiftMap[$lotteryId] = $count;
        }

        $lotterys = [];
        $assetList = [];
        foreach ($deliveryGiftMap as $lotteryId => $count) {
            $lottery = GashaponSystem::getInstance()->findLottery($lotteryId);
            if ($lottery) {
                $lotterys[] = $lottery;
                $assetList[] = [$lottery->reward->assetId, $lottery->reward->count*$count, $biEvent];
            }
        }

        AssetUtils::addAssets($userId, $assetList, $timestamp);
        return $lotterys;
    }

    private function processRank($userId, $lotterys, $timestamp) {
        $redis = RedisCommon::getInstance()->getRedis();
        foreach ($lotterys as $lottery){
            $model = new GashaponRewardModel($userId, $lottery->reward->assetId, $lottery->reward->count, $timestamp);
            GashaponRewardModelDao::getInstance()->saveReward($model);

            // 滚动
            $jinliRankKey = 'rank_gashapon_scroll';
            $redis->lPush($jinliRankKey, json_encode(GashaponRewardModelDao::getInstance()->modelToData($model)));
            $redis->lTrim($jinliRankKey, 0, 50 - 1);
        }
    }

}

