<?php


namespace app\domain\pay;

use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetItem;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\bi\BIReport;
use app\domain\Config;
use app\domain\dao\MonitoringModelDao;
use app\domain\exceptions\FQException;
use app\domain\pay\dao\OrderModelDao;
use app\domain\pay\dao\PayChannelModelDao;
use app\domain\pay\dao\UserChargeStaticsModelDao;
use app\domain\pay\model\Order;
use app\domain\redpacket\RedPacketService;
use app\domain\thirdpay\chinaums\PayConstant;
use app\domain\user\dao\MemberDetailModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\UserRepository;
use app\event\BuyVipEvent;
use app\event\ChargeEvent;
use app\service\PayService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use constant\FirstChargeConstant;
use think\facade\Log;
use Exception;

class ChargeService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChargeService();
        }
        return self::$instance;
    }

    public function isFirstCharged($userId)
    {
        $statics = UserChargeStaticsModelDao::getInstance()->loadUserChargeStatics($userId);
        return ($statics != null && $statics->chargeTimes > 0);
    }

    // 仅充值过金额，不包含vip等充值
    public function isOnlyCharged($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->sIsMember(FirstChargeConstant::USER_RECHARGED_BEEN, $userId);
    }


    private function getChargeList($userId, $areaName)
    {
        $ret = [];
        $area = ProductSystem::getInstance()->getArea($areaName);
        if ($area != null) {
            $shelvesNames = ['products'];
            if (!$this->isFirstCharged($userId)) {
                $shelvesNames[] = 'firstPay';
            }

            foreach ($shelvesNames as $shelvesName) {
                $shelves = $area->findShelves($shelvesName);
                if ($shelves != null) {
                    $ret = array_merge($ret, $shelves->products);
                }
            }
        }

        return $ret;
    }

    public function getBeanCoinList()
    {
        $productsJsonObj = Config::getInstance()->getBeanCoinConf();
        $productMap = [];
        foreach ($productsJsonObj as $productJson) {
            $product = new Product();
            $product->decodeFromJson($productJson);
            $productMap[$product->productId] = $product;
        }
        return $productMap;
    }

    public function getIOSChargeList($userId)
    {
        return $this->getChargeList($userId, 'ios');
    }

    public function getAndroidChargeList($userId)
    {
        return $this->getChargeList($userId, 'android');
    }

    public function getPayChannels($type = 1)
    {
        return PayChannelModelDao::getInstance()->payChannelList($type);
    }

    public function iosBuyProduct($userId, $appStoreProductId, $isActive, $dealId = '', $isAutoRenewal = false)
    {
        if (!empty($dealId)) {
            $order = OrderModelDao::getInstance()->findOrderByDealId($dealId);
            if ($order) {
                return $order;
            }
        }

        $area = ProductSystem::getInstance()->getArea('ios');
        if ($area == null) {
            throw new FQException('充值比例错误', 500);
        }

        if ($isActive == 3) {
            if ($this->isFirstCharged($userId)) {
                throw new FQException('该用户已经参加过活动', 500);
            }
            $product = $area->findByAppStoreProductId($appStoreProductId, 'firstPay');
        } else {
            $product = $area->findByAppStoreProductId($appStoreProductId);
        }

        if ($product == null) {
            throw new FQException('充值比例错误', 500);
        }

        // 自动扣款的商品取自动扣款的价格
        $price = $product->price;
        if ($isAutoRenewal && $product->autoRenewalPrice){
            $price = $product->autoRenewalPrice;
        }

        return $this->buyProduct($userId, $product, $price, 22, '苹果支付', $isActive, $dealId);
    }

    public function newIosBuyProduct($userId, $productId, $redParams, $dealId = '')
    {
        if (!empty($dealId)) {
            $order = OrderModelDao::getInstance()->findOrderByDealId($dealId);
            if ($order) {
                return $order;
            }
        }

        $area = ProductSystem::getInstance()->getArea('ios');
        if ($area == null) {
            throw new FQException('充值比例错误', 500);
        }

        $isActive = 3;
        $product = $area->findByProductId($productId, 'firstPay');
        if ($product) {
            if ($this->isOnlyCharged($userId)) {
                FirstChargeService::getInstance()->setFirstPayNotice($userId);
                Log::info(sprintf('chargeService newIosBuyProduct repeat first_charge userId=%d ', $userId));
                throw new FQException('该用户已经参加过活动', FirstChargeConstant::FIRST_CHARGE_ERROR_CODE);
            }
        } else {
            $isActive = 0;
            $product = $area->findByProductId($productId);
        }
        if ($product == null) {
            throw new FQException('充值比例错误', 500);
        }

        $order = $this->buyProduct($userId, $product, $product->price, 22, '苹果支付', $isActive, $dealId, $redParams);
        return [$product, $order];
    }

    public function androidBuyRedPacketProduct($userId, $productId, $payChannel, $config)
    {
        $product = ProductSystem::getInstance()->findProduct($productId);

        if ($product == null) {
            throw new FQException('充值比例错误', 500);
        }

        $payChannelModel = PayChannelModelDao::getInstance()->findByChannelId($payChannel);

        if (!in_array($payChannel, [1, 2, 3])) {
            throw new FQException('充值渠道错误', 500);
        }

        $order = $this->buyProduct($userId, $product, $product->price, $payChannel, $payChannelModel->content, 0);

        $payResult = PayService::getInstance()->pay($order, $config, true);

        return [$payResult, $order->orderId];
    }

    public function androidBuyVipProduct($userId, $rmb, $payChannel, $config, $code)
    {
        $product = ProductSystem::getInstance()->findProductByRmbInShelves(ProductAreaNames::$ANDROID,
            [
                ProductShelvesNames::$VIP,
                ProductShelvesNames::$SVIP,
                ProductShelvesNames::$FIRST_SVIP_AUTO,
                ProductShelvesNames::$FIRST_VIP_AUTO,
                ProductShelvesNames::$VIP_AUTO,
                ProductShelvesNames::$SVIP_AUTO,
            ], $rmb);

        if (!$product) {
            throw new FQException('充值比例错误', 500);
        }

        $payChannelModel = PayChannelModelDao::getInstance()->findByChannelId($payChannel);

        if (!in_array($payChannel, [1, 2, 3,
            PayConstant::CHINAUMS_APP_ALI_CHANNEL,
            PayConstant::CHINAUMS_WECHAT_APPLET_CHANNEL
        ])) {
            throw new FQException('充值渠道错误', 500);
        }

        $order = $this->buyProduct($userId, $product, $rmb, $payChannel, $payChannelModel->content, 0);

        $payResult = PayService::getInstance()->pay($order, $config, false, $code);

        return [$payResult, $order->orderId];
    }

    public function newAndroidBuyVipProduct($userId, $productId, $payChannel, $config)
    {
        $product = ProductSystem::getInstance()->findProductByProductIdInShelves(ProductAreaNames::$ANDROID,
            [ProductShelvesNames::$VIP, ProductShelvesNames::$SVIP], $productId);
        if (!$product) {
            throw new FQException('充值比例错误', 500);
        }

        $payChannelModel = PayChannelModelDao::getInstance()->findByChannelId($payChannel);

        if (!in_array($payChannel, [1, 2, 3])) {
            throw new FQException('充值渠道错误', 500);
        }

        $order = $this->buyProduct($userId, $product, $product->price, $payChannel, $payChannelModel->content, 0);

        $payResult = PayService::getInstance()->pay($order, $config);

        return [$payResult, $order->orderId];
    }

    public function androidBuyProduct($userId, $rmb, $payChannel, $isActive, $config, $code ,$productId = 0)
    {
        $area = ProductSystem::getInstance()->getArea('android');
        if ($area == null) {
            throw new FQException('充值比例错误', 500);
        }
        if ($isActive == 3) {
            if ($this->isFirstCharged($userId)) {
                throw new FQException('该用户已经参加过活动', 500);
            }
            $product = $area->findByRmb($rmb, 'firstPay');
        } else {
            $product = $area->findByRmb($rmb, 'products');
        }

        // 未传rmb 传productId场景
        if ($product == null && $productId){
            $product = $area->findByProductId($productId, 'firstPay');
            if ($product && $this->isFirstCharged($userId)) {
                Log::info(sprintf('chargeService androidBuyProduct repeat first_charge userId=%d ', $userId));
                throw new FQException('该用户已经参加过活动', FirstChargeConstant::FIRST_CHARGE_ERROR_CODE);
            } else {
                $product = $area->findByProductId($productId);
            }
            $rmb = $product->price;
        }

        if ($product == null) {
            throw new FQException('充值比例错误', 500);
        }

        $payChannelModel = PayChannelModelDao::getInstance()->findByChannelId($payChannel);

//        if (!in_array($payChannel, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16])) {
//            throw new FQException('充值渠道错误', 500);
//        }
        if ($payChannelModel == null) {
            throw new FQException('充值渠道错误', 500);
        }
        $order = $this->buyProduct($userId, $product, $rmb, $payChannel, $payChannelModel->content, $isActive);

        $payResult = PayService::getInstance()->pay($order, $config, false, $code);

        return [$payResult, $order->orderId];
    }

    public function gzhBuyProduct($userId, $rmb, $payChannel, $isActive, $config, $code)
    {
        $area = ProductSystem::getInstance()->getArea('android');
        if ($area == null) {
            throw new FQException('充值比例错误', 500);
        }
        if ($isActive == 3) {
            if ($this->isFirstCharged($userId)) {
                throw new FQException('该用户已经参加过活动', 500);
            }
            $product = $area->findByRmb($rmb, 'firstPay');
        } else {
            $product = $area->findByRmb($rmb, 'products');
        }

        if ($product == null) {
            throw new FQException('充值比例错误', 500);
        }

        $payChannelModel = PayChannelModelDao::getInstance()->findByChannelId($payChannel);
        if ($payChannelModel == null) {
            throw new FQException('充值渠道错误', 500);
        }
        return $this->buyProduct($userId, $product, $rmb, $payChannel, $payChannelModel->content, $isActive);
    }

    public function newBuyProduct($userId, $productId, $payChannel)
    {
        $area = ProductSystem::getInstance()->getArea('android');
        if ($area == null) {
            throw new FQException('充值比例错误', 500);
        }

        $product = $area->findByProductId($productId, 'firstPay');
        if ($product && $this->isOnlyCharged($userId)) {
            FirstChargeService::getInstance()->setFirstPayNotice($userId);
            Log::info(sprintf('chargeService newBuyProduct repeat first_charge userId=%d ', $userId));
            throw new FQException('该用户已经参加过活动', FirstChargeConstant::FIRST_CHARGE_ERROR_CODE);
        } else {
            $product = $area->findByProductId($productId);
        }

        if ($product == null) {
            throw new FQException('充值比例错误', 500);
        }

        $payChannelModel = PayChannelModelDao::getInstance()->findByChannelId($payChannel);
        if ($payChannelModel == null) {
            throw new FQException('充值渠道错误', 500);
        }
        return $this->buyProduct($userId, $product, $product->price, $payChannel, $payChannelModel->content, 0);
    }

    public function buyProduct($userId, $product, $rmb, $payChannel, $content, $isActive, $dealId = '', $redParams = null)
    {
        $monitoringModel = MonitoringModelDao::getInstance()->findByUserId($userId);

        if ($monitoringModel != null) {
            throw new FQException('青少年模式已开启', 500);
        }

        $source = app('request')->header('source', '');
        if ($source != 'chuchu'){
            $mobile = UserModelDao::getInstance()->getBindMobile($userId);
            if ($mobile == null) {
                throw new FQException('您还没有绑定手机号', 500);
            }
        }

        $order = $this->createOrder($userId, $rmb, $product, $payChannel, $content, $isActive, time(), $dealId);

        if (!empty($redParams)) {
            $roomId = $redParams['roomId'];
            $type = ArrayUtil::safeGet($redParams, "type", 0);
            $totalBean = $redParams['bean'];
            $count = $redParams['count'];
            $countdownTime = $redParams['time'];
            RedPacketService::getInstance()->makeRedPacket($userId, $roomId, $order->orderId, $type, $totalBean, $count, $countdownTime);
        }

        return $order;
    }

    public function payAndDeliveryOrderByOrder($order, $dealId, $outParam)
    {
        if ($order->status == OrderStates::$CREATE) {
            $this->payOrderImpl($order, $dealId, $outParam);
        }
        try {
            $product = $this->deliveryOrder($order);
            $assets = [];
            if ($product->deliveryAssets) {
                foreach ($product->deliveryAssets as $assetItem) {
                    $assets[] = [$assetItem->assetId, $assetItem->count];
                }
            }
            Log::info(sprintf('ChargeService::payAndDeliveryOrderByOrder userId=%d orderId=%d productId=%d assets=%s',
                $order->userId, $order->orderId, $product->productId, json_encode($assets)));
        } catch (FQException $e) {
            Log::error(sprintf('ChargeService::payAndDeliveryOrderByOrder userId=%d orderId=%d productId=%d ex=%d:%s trace=%s',
                $order->userId, $order->orderId, $order->productId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        return $order;
    }

    public function payAndDeliveryOrder($orderId, $dealId, $outParam)
    {
        $order = $this->payOrder($orderId, $dealId, $outParam);
        try {
            $product = $this->deliveryOrder($order);
            $assets = [];
            if ($product->deliveryAssets) {
                foreach ($product->deliveryAssets as $assetItem) {
                    $assets[] = [$assetItem->assetId, $assetItem->count];
                }
            }
            Log::info(sprintf('ChargeService::payAndDeliveryOrder userId=%d orderId=%d productId=%d assets=%s',
                $order->userId, $order->orderId, $product->productId, json_encode($assets)));
        } catch (FQException $e) {
            Log::error(sprintf('ChargeService::payAndDeliveryOrder userId=%d orderId=%d productId=%d ex=%d:%s trace=%s',
                $order->userId, $order->orderId, $order->productId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        return $order;
    }

    private function payOrderImpl(Order $order, $dealId, $outParam)
    {
        if ($order->status == OrderStates::$DELIVERY) {
            throw new AlreadyDeliveryException('订单状态错误', 500);
        }
        if ($order->status != 0) {
            throw new FQException('订单状态错误', 500);
        }

        $order->outParam = json_encode($outParam);
        $order->dealId = $dealId;
        $order->status = OrderStates::$PAID;
        $order->paidTime = time();

        if (!OrderModelDao::getInstance()->updateOrder($order->orderId, [
            'status' => $order->status,
            'paid_time' => $order->paidTime,
            'outparam' => $order->outParam,
            'dealid' => $order->dealId,
        ], OrderStates::$CREATE)) {
            throw new FQException('订单状态错误', 500);
        }

        try {
            $addAmount = intval($order->rmb * 100);
            UserChargeStaticsModelDao::getInstance()->addAmountTimes($order->userId, $addAmount, 1);
        } catch (Exception $e) {
            Log::error(sprintf('ChargeService::payOrderImpl orderId=%s userId=%s addAmount=%d ex=%d:%s trace=%s',
                $order->orderId, $order->userId, $addAmount,
                $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
        return $order;
    }

    public function payOrder($orderId, $dealId, $outParam)
    {
        $order = OrderModelDao::getInstance()->loadOrder($orderId);
        if ($order == null) {
            throw new FQException('充值渠道错误', 500);
        }
        return $this->payOrderImpl($order, $dealId, $outParam);
    }

    public function deliveryOrder($order)
    {
        $product = ProductSystem::getInstance()->findProduct($order->productId);
        if ($product == null) {
            throw new FQException('商品不存在', 500);
        }
        if ($order->status != OrderStates::$DELIVERY) {
            $this->deliveryOrderImpl($order, $product, time());
        }
        return $product;
    }

    public function deliveryOrderImpl(Order $order, $product, $timestamp)
    {
        $order->status = OrderStates::$DELIVERY;
        $order->finishTime = $timestamp;
        if (!OrderModelDao::getInstance()->updateOrder($order->orderId, [
            'status' => $order->status,
            'finish_time' => $order->finishTime,
        ], OrderStates::$PAID)) {
            throw new FQException('订单状态错误', 500);
        }

        if ($product->deliveryAssets != null) {
            $vipCount = AssetItem::calcAssetCount($product->deliveryAssets, AssetKindIds::$VIP_MONTH);
            $svipCount = AssetItem::calcAssetCount($product->deliveryAssets, AssetKindIds::$SVIP_MONTH);
            try {
                list($vipOpen, $svipOpen, $vipExpiresTime, $svipExpiresTime) = Sharding::getInstance()->getConnectModel('userMaster', $order->userId)->transaction(function () use (
                    $order, $product, $vipCount, $svipCount, $timestamp
                ) {
                    $vipOpen = false;
                    $svipOpen = false;
                    $vipExpiresTime = 0;
                    $svipExpiresTime = 0;
                    $user = UserRepository::getInstance()->loadUser($order->userId);
                    if ($user == null) {
                        throw new FQException('用户不存在', 500);
                    }
                    $userAssets = $user->getAssets();
                    $biEvent = BIReport::getInstance()->makeChargeDeliveryBIEvent($order->orderId, $product->productId, $order->payChannel);
                    if ($vipCount > 0 || $svipCount > 0) {
                        $vip = $user->getVip($timestamp);
                        $vipOpen = $vip->getVipExpiresTime() <= 0;
                        $svipOpen = $vip->getSvipExpiresTime() <= 0;
                    }
                    $beanTotal = 0;
                    foreach ($product->deliveryAssets as $assetItem) {
                        $userAssets->add($assetItem->assetId, $assetItem->count, $timestamp, $biEvent);
                        if ($assetItem->assetId === AssetKindIds::$BEAN) {
                            $beanTotal += $assetItem->count;
                        }
                    }
//                累加用户冲豆的历史总值,用户召回需要使用;
                    if ($beanTotal > 0) {
                        MemberDetailModelDao::getInstance()->incrUserAmount($order->userId, $beanTotal);
                    }
                    if ($vipCount > 0 || $svipCount > 0) {
                        $vipExpiresTime = $vip->getVipExpiresTime();
                        $svipExpiresTime = $vip->getSvipExpiresTime();
                    }
                    return [$vipOpen, $svipOpen, $vipExpiresTime, $svipExpiresTime];
                });
            } catch (Exception $e) {
                throw $e;
            }

            event(new ChargeEvent($order, $product, $timestamp));

            if ($vipCount > 0) {
                event(new BuyVipEvent($order->userId, $order->orderId, 1, $vipCount, $vipExpiresTime, $vipOpen, $timestamp));
            }

            if ($svipCount > 0) {
                event(new BuyVipEvent($order->userId, $order->orderId, 2, $svipCount, $svipExpiresTime, $svipOpen, $timestamp));
            }
        }

        return [];
    }

    //创建订单
    public function createOrder($userId, $rmb, $product, $payChannel, $content, $isActive, $timestamp, $dealId = '')
    {
        //生成订单号
        $orderId = CommonUtil::createOrderNo($userId);
        $order = new Order();
        $order->orderId = $orderId;
        $order->userId = $userId;
        $order->productId = $product->productId;
        $order->rmb = $rmb;
        $order->bean = $product->bean;
        $order->content = $content;
        $order->status = 0;
        $order->proxyUserId = 0;
        $order->dealId = $dealId;
        $order->createTime = $timestamp;
        $order->payChannel = $payChannel;
        $order->title = '';
        $order->type = ProductSystem::getInstance()->getProductType($product->productId);
        $order->isActive = $isActive;
        $order->channel = '4';

        OrderModelDao::getInstance()->createOrder($order);

        Log::info(sprintf('ChargeService::createOrder userId=%d productId=%d rmb=%.2f channelId=%d isActive=%d',
            $userId, $product->productId, $rmb, $order->payChannel, $isActive));

        return $order;
    }

    public function getFirstPayInfo()
    {
        $androidShelves = ProductSystem::getInstance()->getProductMap(ProductAreaNames::$ANDROID, ProductShelvesNames::$FIRST_PAY);
        $iosShelves = ProductSystem::getInstance()->getProductMap(ProductAreaNames::$IOS, ProductShelvesNames::$FIRST_PAY);
        if ($iosShelves != null) {
            $iosProducts = $this->encodeFirstPayProducts($iosShelves, true);
        } else {
            $iosProducts = [];
        }
        if ($androidShelves != null) {
            $androidProducts = $this->encodeFirstPayProducts($androidShelves, false);
        } else {
            $androidProducts = [];
        }
        return [
            'ios' => $iosProducts,
            'android' => $androidProducts
        ];
    }

    private function encodeFirstPayProducts($products, $flag = false)
    {
        $ret = [];
        foreach ($products as $product) {
            $encodedProduct = $this->encodeFirstPayProduct($product, $flag);
            if ($encodedProduct) {
                $ret[] = $encodedProduct;
            }
        }
        return $ret;
    }

    private function encodeFirstPayProduct($product, $flag)
    {
        if ($flag) {
            return [
                'productId' => $product->productId,
                'appStoreProductId' => $product->appStoreProductId,
                'price' => $product->price,
                'beforePrice' => $product->present,
                'deliveryAssets' => $this->encodeFirstPayProductAssetItem($product->deliveryAssets)
            ];
        }
        return [
            'productId' => $product->productId,
            'price' => $product->price,
            'beforePrice' => $product->present,
            'deliveryAssets' => $this->encodeFirstPayProductAssetItem($product->deliveryAssets)
        ];
    }

    private function encodeFirstPayProductAssetItem($deliveryAssets)
    {
        if (empty($deliveryAssets)) {
            return null;
        }
        $ret = [];
        foreach ($deliveryAssets as $asset) {
            $assetInfo = AssetSystem::getInstance()->findAssetKind($asset->assetId);
            $assetArr = null;
            if ($asset) {
                $assetArr = [
                    'displayName' => $assetInfo->displayName,
                    'unit' => $assetInfo->unit,
                    'image' => CommonUtil::buildImageUrl($assetInfo->image),
                    'count' => $asset->count,
                    'assetType' => property_exists($assetInfo, 'propKind') ? $assetInfo->propKind->type : ''
                ];
            }
            $ret[] = $assetArr;
        }
        return $ret;
    }

    public function getChargeContent($model): array
    {
        $beanChargeMap = [
            '1' => '支付宝app支付',
            '2' => '支付宝网页支付',
            '3' => '微信app支付',
            '4' => '公众号支付',
            '13' => '微信H5支付',
            '15' => '微信扫码支付',
            '16' => '支付宝H5支付',
            '22' => '苹果支付',
            'default' => '支付'
        ];
        $key = $model->payChannel;
        return
            [
                'title' => [
                    ['type' => 'txt', 'content' => ArrayUtil::safeGet($beanChargeMap, $key, '支付')]
                ],
                'timestamp' => $model->finishTime,
                'number' => (string)$model->rmb,
                'status' => (int)$model->status,   //0未支付，2已支付
            ];
    }

}