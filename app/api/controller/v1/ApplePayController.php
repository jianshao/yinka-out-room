<?php
/**
 * 苹果内购
 * yond
 *
 */

namespace app\api\controller\v1;

use app\api\view\v1\VipView;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\pay\ChargeService;
use app\domain\pay\dao\OrderModelDao;
use app\domain\pay\OrderStates;
use app\domain\pay\ProductAreaNames;
use app\domain\pay\ProductShelvesNames;
use app\domain\pay\ProductSystem;
use app\domain\pay\ProductTypes;
use app\domain\redpacket\RedPacketService;
use app\domain\redpacket\RedPacketSystem;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UnderAgeService;
use app\domain\user\service\WalletService;
use app\domain\vip\dao\VipModelDao;
use app\event\IosChargeEvent;
use app\service\ApplePayService;
use app\service\LockService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\Error;
use think\facade\Log;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class ApplePayController extends ApiBaseController
{
    private $ttl = 30000;

    public function iosChargeList()
    {
        $userId = intval($this->headUid);
        $products = ChargeService::getInstance()->getIOSChargeList($userId);
        $ret = [];
        $defaultId = 0;
        foreach ($products as $product) {
            if ($defaultId === 0) {
                $defaultId = $product->productId;
            }
            $itemData = [
                'id' => $product->productId,
                'rmb' => $product->price,
                'diamond' => $product->bean,
                'present' => $product->present,
                'chargemsg' => $product->chargeMsg,
                'coinimg' => CommonUtil::buildImageUrl($product->image),
                'vipgift' => 0,
                'iosflag' => $product->appStoreProductId,
                'status' => $product->status,
            ];
            if ($product->productId === 104) {
                continue;
            }
            $ret[] = $itemData;
        }
        return rjson([
            'charge_list' => $ret,
            'defaultId' => $defaultId,
        ]);
    }

    private function makeDealId($transactionId)
    {
        return 'APPLEPAY' . $transactionId;
    }

    //苹果发红包
    public function packetsPayment()
    {
        $paymentData = Request::param();
        $userId = intval($this->headUid);
        $roomId = intval($paymentData['room_id']);
        $totalBean = intval($paymentData['coin']);
        $count = intval($paymentData['num']);
        $countDownTime = (int)($paymentData['time']);
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if($isUnderAge){
            return rjson([],500,'未满18周岁用户暂不支持此功能');
        }
        // 验证支付状态
        $result = ApplePayService::getInstance()->checkApplePay($paymentData['receipt'], $userId, $this->config);
        if ($result) {
            $orders = $this->processApplePay($userId, $paymentData, $result);
            // 根据订单里的红包
            $productMap = ProductSystem::getInstance()->getProductMap(ProductAreaNames::$IOS, ProductShelvesNames::$RED_PACKET);
            foreach ($orders as $order) {
                if (array_key_exists($order->productId, $productMap)) {
                    // 发红包
                    RedPacketService::getInstance()->makeAndSendRedPacket($userId, $roomId, 3, $totalBean, $count,
                        $order->orderId, $order->dealId, $countDownTime);
                }
            }
            return rjson([
                'coin' => 0
            ]);
        }
        return rjson([], 4002, '订单信息错误,请联系客服');
    }

    public function viewVip($vipModel)
    {
        if ($vipModel->level == 1) {
            $vipExpTime = $vipModel->vipExpiresTime;
        } elseif ($vipModel->level == 2) {
            $vipExpTime = $vipModel->svipExpiresTime;
        } else {
            $vipExpTime = 0;
        }
        $time = time();
        return [
            'vip_exp_time' => floor(($vipExpTime - $time) / 86400),
            'is_vip' => $vipModel->level
        ];
    }

    private function processApplePay($userId, $paymentData, $payDetails)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $ret = [];
        $curTime = time();
        foreach ($payDetails as $key => $value) {
            $dealId = $this->makeDealId($value['transaction_id']);

            $res = $redis->set('Lock:' . $dealId, 1, ['NX', 'PX' => $this->ttl]);
            if (!$res) {
                Log::warning(sprintf('ApplePayController processApplePay userId:%s dealId:%s', $userId, $dealId));
                continue;
            }

            $isActive = empty($paymentData['is_active']) ? 0 : $paymentData['is_active'];
            $appStoreProductId = ArrayUtil::safeGet($value, 'product_id');
            $chargeTime = intval(intval($value['purchase_date_ms']) / 1000);

            // 大于10天的不处理
            if ($curTime - $chargeTime < 86400 * 10) {
                try {
                    $order = ChargeService::getInstance()->iosBuyProduct($userId, $appStoreProductId, $isActive, $dealId);
                    if ($order->status == OrderStates::$CREATE) {
                        ChargeService::getInstance()->payAndDeliveryOrderByOrder($order, $dealId, '');
                        Log::info(sprintf('ProcessApplePay ok userId=%d dealId=%s isActive=%d appStoreProductId=%s productId=%d transactionId=%s',
                            $userId, $dealId, $isActive, $appStoreProductId, $order->productId, $value['transaction_id']));
                        $ret[] = $order;
                    }
                } catch (FQException $e) {
                    Log::error(sprintf('ProcessApplePay failed userId=%d dealId=%s isActive=%d appStoreProductId=%s  transactionId=%s ex=%d:%s',
                        $userId, $dealId, $isActive, $appStoreProductId, $value['transaction_id'], $e->getCode(), $e->getMessage()));
                }
            }
        }
        return $ret;
    }

    //苹果充值会员
    public function chargePayment()
    {
        $paymentData = Request::param();
        $userId = intval($this->headUid);
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if($isUnderAge){
            return rjson([],500,'未满18周岁用户暂不支持此功能');
        }
        // 验证支付状态
        $result = ApplePayService::getInstance()->checkApplePay($paymentData['receipt'], $userId, $this->config);
        if ($result) {
            $this->processApplePay($userId, $paymentData, $result);
            $vipModel = VipModelDao::getInstance()->loadVip($userId);
            $time = time();
            $result = VipView::viewVip($vipModel, $time);
            $result['coin'] = 0;
            return rjson($result);
        }

        return rjson([], 4002, '订单信息错误,请联系客服');
    }


    //苹果支付
    public function payment()
    {
        $paymentData = Request::param();
        $userId = intval($this->headUid);
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if($isUnderAge){
            return rjson([],500,'未满18周岁用户暂不支持此功能');
        }
        // 验证支付状态
        $result = ApplePayService::getInstance()->checkApplePay($paymentData['receipt'], $userId, $this->config);
        if ($result) {
            $this->processApplePay($userId, $paymentData, $result);
            $beanModel = BeanModelDao::getInstance()->loadBean($userId);
            return rjson([
                'coin' => $beanModel->balance()
            ]);
        }
        return rjson([], 4002, '订单信息错误,请联系客服');
    }

    public function iosPayNotice()
    {
        $userId = intval($this->headUid);
        event(new IosChargeEvent($userId, time()));
        return rjson();
    }


    /**
     * @info  ios创建订单 新版
     * @param $type string 类型 ['redPack','product','vip','svip']
     * @param $data string 数据内容 jsonstr ['productId'=>102,"roomId"=>100031,"type"=>"","bean"=>"55","count"=>3,"time"=>30]
     * @return \think\response\Json
     * @throws FQException
     */
    public function iosBuyProduct()
    {
        $userId = intval($this->headUid);
        $type = Request::param('type', '');
        $strData = Request::param('data', '');
        if (empty($strData)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $paramMap = json_decode($strData, true);
        if ($type === "redPack") {
            $redParams = $paramMap;
            $productId = ArrayUtil::safeGet($paramMap, 'productId', 0);
        } else {
            $productId = ArrayUtil::safeGet($paramMap, 'productId', 0);
            $redParams = null;
        }
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel && $userModel->cancelStatus != 0) {
            throw new FQException('账号已注销或申请注销中，无法充值', 500);
        }

        $area = ProductSystem::getInstance()->getArea('android');
        if ($area == null) {
            throw new FQException('充值比例错误', 500);
        }

        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if($isUnderAge){
            return rjson([],500,'未满18周岁用户暂不支持此功能');
        }

        if ($type === 'redPack') {
            $displayTime = $redParams['time'];
            $seconds = RedPacketSystem::getInstance()->getSecondsByDisplay($displayTime);
            if ($seconds == null) {
                throw new FQException('发红包选项错误,请重试', 500);
            }
            $redParams['time'] = $seconds;
            $redParams['type'] = 3;
        }
        list($product, $order) = ChargeService::getInstance()->newIosBuyProduct($userId, $productId, $redParams);
        event(new IosChargeEvent($userId, time()));

        $ret = [
            'userId' => $userId,
            'productId' => $productId,
            'appStoreProductId' => $product->appStoreProductId,
            'orderId' => $order->orderId,
        ];
        return rjson($ret);
    }


    //ios发货
    public function iosPayMent()
    {
        $receipt = Request::param('receiptData');
        $transactionId = Request::param('transactionIdentifier');
        $orderId = Request::param('orderId');
        $userId = intval($this->headUid);

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel && $userModel->cancelStatus != 0) {
            throw new FQException('账号已注销或申请注销中，无法充值', 500);
        }
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if($isUnderAge){
            return rjson([],500,'未满18周岁用户暂不支持此功能');
        }

        // 验证支付状态
        $result = ApplePayService::getInstance()->checkApplePay($receipt, $userId, $this->config);
        if ($result) {
            list($orders, $transactionIds) = $this->newProcessApplePay($userId, $orderId, $transactionId, $result);
            foreach ($orders as $order) {
                if ($order->type == ProductTypes::$REDPACKET) {
                    RedPacketService::getInstance()->payAndSendRedPacketByOrderId($order->orderId, $order->dealId);
                }
            }

            $vipModel = VipModelDao::getInstance()->loadVip($userId);
            $data['userInfo'] = [
                'vip' => $this->viewVip($vipModel)
            ];
            $data['wallet'] = WalletService::getInstance()->getWallet($userId);
            $data['transactionIds'] = $transactionIds;
            return rjson($data);
        }
        return rjson([], 4002, '订单信息错误,请联系客服');
    }


    private function newProcessApplePay($userId, $orderId, $transactionId, $payDetails)
    {
        $ret = [];
        $transactionIds = [];
        $curTime = time();
        $area = ProductSystem::getInstance()->getArea('ios');
        foreach ($payDetails as $key => $value) {
            $dealId = $this->makeDealId($value['transaction_id']);
            $appStoreProductId = ArrayUtil::safeGet($value, 'product_id');
            $chargeTime = intval(intval($value['purchase_date_ms']) / 1000);

            // 大于2天的不处理
            if ($curTime - $chargeTime < 86400 * 10) {
                try {
                    $order = null;
                    if ($transactionId == $value['transaction_id']) {
                        $product = $area->findByAppStoreProductId($appStoreProductId);
                        if (!$product->isAutoRenewal) {
                            $order = OrderModelDao::getInstance()->loadOrder($orderId);
                        }
                    }
                    if ($order == null) {
                        $order = ChargeService::getInstance()->iosBuyProduct($userId, $appStoreProductId, 0, $dealId);
                    }

                    if ($order->status == OrderStates::$CREATE) {
                        ChargeService::getInstance()->payAndDeliveryOrderByOrder($order, $dealId, '');
                        Log::info(sprintf('ProcessApplePay ok userId=%d orderId=%s transactionId=%s dealId=%s appStoreProductId=%s productId=%d',
                            $userId, $orderId, $transactionId, $dealId, $appStoreProductId, $order->productId));
                        $ret[] = $order;
                        $transactionIds[] = $value['transaction_id'];
                    }
                } catch (FQException $e) {
                    Log::error(sprintf('ProcessApplePay failed userId=%d orderId=%s transactionId=%s dealId=%s appStoreProductId=%s ex=%d:%s',
                        $userId, $orderId, $transactionId, $dealId, $appStoreProductId, $e->getCode(), $e->getMessage()));
                }
            }
        }
        return [$ret, $transactionIds];
    }


    public function appStoreProductList()
    {
        $area = ProductSystem::getInstance()->getArea('ios');
        $productIds = $area->getAppStoreProductIdList();
        $data['list'] = array_values($productIds);
        return rjson($data);
    }

}