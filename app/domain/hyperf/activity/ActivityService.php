<?php


namespace app\domain\hyperf\activity;

use app\core\mysql\Sharding;
use app\domain\prop\PropKindBubble;
use app\query\prop\service\PropQueryService;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\AssetNotEnoughException2;
use app\domain\exceptions\FQException;
use app\domain\game\GameService;
use app\domain\gift\GiftSystem;
use app\domain\mall\MallIds;
use app\domain\mall\service\MallService;
use app\domain\user\UserRepository;
use think\facade\Log;

class ActivityService
{

    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ActivityService();
        }
        return self::$instance;
    }

    public function collectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp, $activityType) {
        list($consume, $balance) = $this->tryCollectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp, $activityType);
        if ($consume >= $totalPrice) {
            // 扣费成功
            return $balance;
        }

        // 费用不足
        if (!$autoBuy) {
            throw new AssetNotEnoughException2(GameService::getInstance()->priceAssetId, '积分不足', 211);
        }

        // 计算需要购买商品数量
        $rem = $totalPrice - $balance;
        $goods = GameService::getInstance()->getGoods();
        $countPerGoods = $goods->deliveryAsset->count;
        $goodsCount = intval(($rem + ($countPerGoods - 1)) / $countPerGoods);

        // 购买商品
        try {
            MallService::getInstance()->buyGoodsByGoods($userId, $goods, $goodsCount, MallIds::$GAME, $activityType);
        } catch (AssetNotEnoughException $e) {
            throw new AssetNotEnoughException2(AssetKindIds::$BEAN, '积分不足', 211);
        }

        list($consume, $balance) = $this->tryCollectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp, $activityType);
        if ($consume >= $totalPrice) {
            // 扣费成功
            return $balance;
        }

        throw new AssetNotEnoughException2(GameService::getInstance()->priceAssetId, '积分不足', 211);
    }

    private function tryCollectFee($userId, $roomId, $box, $count, $totalPrice, $autoBuy, $timestamp, $activityType) {
        $boxId = $box['boxId'];
        $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, $activityType, $boxId, $count);
        return AssetUtils::consumeAsset($userId, GameService::getInstance()->priceAssetId, $totalPrice, $timestamp, $biEvent);
    }

    public function deliveryGifts($userId, $roomId, $box, $count, $giftMap, $specialGiftId, $timestamp, $activityType) {
        try {
            $deliveryGiftMap = [];
            foreach ($giftMap as $giftId => $giftCount) {
                $deliveryGiftMap[$giftId] = $giftCount;
            }
            if ($specialGiftId != null) {
                if (array_key_exists($specialGiftId, $deliveryGiftMap)) {
                    $deliveryGiftMap[$specialGiftId] += 1;
                } else {
                    $deliveryGiftMap[$specialGiftId] = 1;
                }
            }
            $boxId = $box['boxId'];
            $assetList = [];
            foreach ($deliveryGiftMap as $giftId => $giftCount) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
                if ($giftKind) {
                    $giftValue = $giftKind->price != null ? $giftKind->price->count * $giftCount : 0;
                    $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, $activityType, $boxId, $count, $giftValue);
                    $assetList[] = [AssetUtils::makeGiftAssetId($giftId), $giftCount, $biEvent];
                }
            }
            AssetUtils::addAssets($userId, $assetList, $timestamp);
        } catch (\Exception $e) {
            throw new FQException('发货异常', 500);
        }
    }

    public function newerTask($event) {
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $event->userId)->transaction(function () use ($event){
                $user = UserRepository::getInstance()->loadUser($event->userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $dailyService = $user->getTasks()->getNewerTask($event->timestamp);
                $dailyService->handleDomainEvent($event);
            });
        } catch (\Exception $e) {
            Log::error(sprintf('TaskEventHandler::onNewerHandler userId=%d ex=%d:%s',
                $event->userId, $e->getCode(), $e->getMessage()));
        }
    }

    public function getUserBubble($userId) {
        $bubble = PropQueryService::getInstance()->getWaredProp($userId, PropKindBubble::$TYPE_NAME);
        $data['attires'] = $bubble != null ? [$bubble->kind->kindId] : null;
        $data['bubble'] = PropQueryService::getInstance()->encodeBubbleInfo($bubble);
        return $data;
    }
}