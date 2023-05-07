<?php

namespace app\domain\mall\service;

use app\core\mysql\Sharding;
use app\domain\asset\AssetItem;
use app\domain\bi\BIReport;
use app\domain\events\BuyGoodsDomainEvent;
use app\domain\events\ReceiveGoodsDomainEvent;
use app\domain\events\SendGoodsDomainEvent;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use app\domain\mall\Goods;
use app\domain\mall\MallSystem;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use app\event\BuyGoodsEvent;
use think\facade\Log;
use Exception;

/**
 * 商城服务
 */
class MallService
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MallService();
        }
        return self::$instance;
    }

    /**
     * 根据购买数量查找价格
     *
     * @param $goods
     * @param $count
     * @return mixed
     * @throws Exception
     */
    private function ensureCountPrice($goods, $count) {
        $singleItem = null;
        foreach ($goods->priceList as $goodsPrice) {
            if ($count == $goodsPrice->count) {
                return $goodsPrice->assetItem;
            }
            if ($goodsPrice->count == 1) {
                $singleItem = $goodsPrice->assetItem;
            }
        }
        if ($singleItem != null) {
            return new AssetItem($singleItem->assetId, $singleItem->count * $count);
        }
        throw new FQException('购买数量错误', 500);
    }

    /**
     * 用户购买商品
     * 
     * @param userId: 谁购买
     * @param goodsId: 商品ID
     * @param count: 购买多少个
     * @param from: 从哪儿来的，可能后期统计需要
     * 
     */
    public function buyGoodsByGoodsId($userId, $goodsId, $count, $mallId, $from, $roomId=0) {
        Log::debug(sprintf('MallService::buyGoodsByGoodsId Enter userId=%d goodsId=%d count=%d mallId=%s from=%s roomId=%d',
            $userId, $goodsId, $count, $mallId, $from, $roomId));

        $goods = MallSystem::getInstance()->findGoods($goodsId);
        if ($goods == null) {
            throw new FQException('此商品不存在', 500);
        }

        return $this->buyGoodsByGoods($userId, $goods, $count, $mallId, $from, $roomId);
    }

    /**
     * 用户购买商品
     *
     * @param userId: 谁购买
     * @param goodsId: 商品ID
     * @param count: 购买多少个
     * @param from: 从哪儿来的，可能后期统计需要
     *
     */
    public function buyGoodsByGoods($userId, $goods, $count, $mallId, $from, $roomId=0) {
        Log::debug(sprintf('MallService::buyGoodsByGoods Enter userId=%d goodsId=%d count=%d mallId=%s from=%s roomId=%d',
            $userId, $goods->goodsId, $count, $mallId, $from, $roomId));

        if ($count < 0) {
            throw new FQException('参数错误', 500);
        }

        if (!is_integer($count)) {
            throw new FQException('参数错误', 500);
        }

        if ($goods->state != Goods::$ST_IN_SHELVES) {
            throw new FQException('此商品已下架', 500);
        }

        if (!$goods->isBuy()) {
            throw new FQException('此商品不可购买', 500);
        }

        // 查看count是否在priceList中存在
        $price = $this->ensureCountPrice($goods, $count);

        $timestamp = time();

        $balance = $this->buyGoodsImpl($userId, $goods, $price, $count, $mallId, $from, $timestamp);

        Log::info(sprintf('BuyGoodsOk userId=%d goodsId=%d count=%d mallId=%s from=%s consume=%s:%d:%d delivery=%s:%d',
            $userId, $goods->goodsId, $count, $mallId, $from,
            $price->assetId, $price->count, $balance,
            $goods->deliveryAsset->assetId, $goods->deliveryAsset->count));

        event(new BuyGoodsEvent($userId, $roomId, $goods->goodsId, $price, $goods->deliveryAsset, $count, $mallId, $from, $balance,$timestamp));

        return $balance;
    }

    /**
     * 实际购买函数，在事务中运行
     */
    private function buyGoodsImpl($userId, $goods, $price, $count, $mallId, $from, $timestamp) {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use(
                $userId, $price, $timestamp, $goods, $count, $mallId, $from
            ) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                if (empty($user->getUserModel()->username)) {
                    throw new FQException('您还没有绑定手机号', 5100);
                }

                $userAssets = $user->getAssets();

                if ($userAssets->balance($price->assetId, $timestamp) < $price->count) {
                    throw new AssetNotEnoughException('余额不足,请充值', 211);
                }

                // 消耗
                $biEvent = BIReport::getInstance()->makeBuyGoodsBIEvent($mallId, $goods->goodsId, $count, $from);
                $balance = $userAssets->consume($price->assetId, $price->count, $timestamp, $biEvent);

                $deliveryCount = intval($goods->deliveryAsset->count * $count);

                // 发货
                $userAssets->add($goods->deliveryAsset->assetId, $deliveryCount, $timestamp, $biEvent);

                event(new BuyGoodsDomainEvent($user, $price, $goods->deliveryAsset, $count, $mallId, $from, $timestamp));
                return $balance;
            });
        } catch (Exception $e) {
            if ($e instanceof FQException) {
                Log::warning(sprintf('BuyGoodsException userId=%d goodsId=%d count=%d mallId=%s from=%s ex=%d:%s',
                    $userId, $goods->goodsId, $count, $mallId, $from,
                    $e->getCode(), $e->getMessage()));
            } else {
                Log::error(sprintf('BuyGoodsException userId=%d goodsId=%d count=%d mallId=%s from=%s ex=%d:%s trace=%s',
                    $userId, $goods->goodsId, $count, $mallId, $from,
                    $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    /**
     * 用户赠送商品
     *
     * @param userId: 购买人
     * @param receivedId: 被赠送人
     * @param goodsId: 商品ID
     * @param count: 购买多少个
     * @param from: 从哪儿来的，可能后期统计需要
     *
     */
    public function sendGoodsByGoodsId($userId, $receivedId, $goodsId, $count, $mallId, $from, $roomId=0) {
        Log::debug(sprintf('MallService::sendGoodsByGoodsId Enter userId=%d receivedId=%d goodsId=%d count=%d mallId=%s from=%s roomId=%d',
            $userId, $receivedId, $goodsId, $count, $mallId, $from, $roomId));

        $goods = MallSystem::getInstance()->findGoods($goodsId);
        if ($goods == null) {
            throw new FQException('此商品不存在', 500);
        }

        $userModel = UserModelDao::getInstance()->loadUserModel($receivedId);
        if ($userModel == null) {
            throw new FQException('此用户不存在', 500);
        }

        return $this->sendGoodsByGoods($userId, $receivedId, $goods, $count, $mallId, $from, $roomId);
    }

    public function sendGoodsByGoods($userId, $receivedId, $goods, $count, $mallId, $from, $roomId=0) {
        Log::debug(sprintf('MallService::sendGoodsByGoods Enter userId=%d receivedId=%d goodsId=%d count=%d mallId=%s from=%s roomId=%d',
            $userId, $receivedId, $goods->goodsId, $count, $mallId, $from, $roomId));

        if (!is_integer($count)) {
            throw new FQException('参数错误', 500);
        }

        if ($goods->state != Goods::$ST_IN_SHELVES) {
            throw new FQException('此商品已下架', 500);
        }

        if (!$goods->isSend()) {
            throw new FQException('此商品不可赠送', 500);
        }

        // 查看count是否在priceList中存在
        $price = $this->ensureCountPrice($goods, $count);

        $timestamp = time();

        $balance = $this->sendGoodsImpl($userId, $receivedId, $goods, $price, $count, $mallId, $from, $timestamp);
        $this->receiveGoodsImpl($userId, $receivedId, $goods, $count, $mallId, $from, $timestamp);

        Log::info(sprintf('SendGoodsOk userId=%d receivedId=%d goodsId=%d count=%d mallId=%s from=%s consume=%s:%d:%d delivery=%s:%d',
            $userId, $receivedId, $goods->goodsId, $count, $mallId, $from,
            $price->assetId, $price->count, $balance,
            $goods->deliveryAsset->assetId, $goods->deliveryAsset->count));


        return $balance;
    }

    /**
     * 实际赠送函数，在事务中运行
     */
    private function sendGoodsImpl($userId, $receivedId, $goods, $price, $count, $mallId, $from, $timestamp) {
        try {
           return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use(
               $userId, $receivedId, $goods, $price, $count, $mallId, $from, $timestamp
            ) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                if (empty($user->getUserModel()->username)) {
                    throw new FQException('您还没有绑定手机号', 5100);
                }

                $userAssets = $user->getAssets();

                if ($userAssets->balance($price->assetId, $timestamp) < $price->count) {
                    throw new AssetNotEnoughException('余额不足,请充值', 211);
                }

                // 消耗
                $biEvent = BIReport::getInstance()->makeSendGoodsBIEvent($mallId, $goods->goodsId, $count, $receivedId, $from);
                $balance = $userAssets->consume($price->assetId, $price->count, $timestamp, $biEvent);

                event(new SendGoodsDomainEvent($user, $receivedId, $price, $count, $timestamp));
                return $balance;
            });
        } catch (Exception $e) {
            if ($e instanceof FQException) {
                Log::warning(sprintf('SendGoodsException userId=%d receivedId=%d goodsId=%d count=%d mallId=%s from=%s ex=%d:%s',
                    $userId, $receivedId, $goods->goodsId, $count, $mallId, $from,
                    $e->getCode(), $e->getMessage()));
            } else {
                Log::error(sprintf('SendGoodsException userId=%d receivedId=%d goodsId=%d count=%d mallId=%s from=%s ex=%d:%s trace=%s',
                    $userId, $receivedId, $goods->goodsId, $count, $mallId, $from,
                    $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    private function receiveGoodsImpl($userId, $receivedId, $goods, $count, $mallId, $from, $timestamp)
    {
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $receivedId)->transaction(function () use(
                $userId, $receivedId, $goods, $count, $mallId, $from, $timestamp
            ) {
                $user = UserRepository::getInstance()->loadUser($receivedId);
                $userAssets = $user->getAssets();
                // 发货
                $biEvent = BIReport::getInstance()->makeReceiveGoodsBIEvent($mallId, $goods->goodsId, $count, $userId, $from);
                $deliveryCount = intval($goods->deliveryAsset->count * $count);
                $userAssets->add($goods->deliveryAsset->assetId, $deliveryCount, $timestamp, $biEvent);

                event(new ReceiveGoodsDomainEvent($user, $receivedId, $goods->deliveryAsset, $count, $timestamp));
            });
        } catch (Exception $e) {
            if ($e instanceof FQException) {
                Log::warning(sprintf('ReceiveGoodsException userId=%d receivedId=%d goodsId=%d count=%d mallId=%s from=%s ex=%d:%s',
                    $userId, $receivedId, $goods->goodsId, $count, $mallId, $from,
                    $e->getCode(), $e->getMessage()));
            } else {
                Log::error(sprintf('ReceiveGoodsException userId=%d receivedId=%d goodsId=%d count=%d mallId=%s from=%s ex=%d:%s trace=%s',
                    $userId, $receivedId, $goods->goodsId, $count, $mallId, $from,
                    $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }
}