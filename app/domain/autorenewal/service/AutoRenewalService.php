<?php


namespace app\domain\autorenewal\service;

use app\common\RedisCommon;
use app\domain\autorenewal\dao\AutoRenewalAgreementModelDao;
use app\domain\autorenewal\model\AutoRenewalAgreementModel;
use app\domain\exceptions\FQException;
use app\domain\pay\AlreadyDeliveryException;
use app\domain\pay\ChargeService;
use app\domain\pay\dao\OrderModelDao;
use app\domain\pay\OrderStates;
use app\domain\pay\ProductSystem;
use app\domain\vip\constant\VipConstant;
use app\domain\vip\service\VipService;
use app\service\ApplePayService;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * @desc 自动续费相关支付宝接口调用
 * Class AutoPayAlipayService
 * @package app\domain\pay\service
 */
class AutoRenewalService
{
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AutoRenewalService();
        }
        return self::$instance;
    }

    /**
     * @desc 根据支付宝回调生成签约记录
     * @param $params
     * @throws \Exception
     */
    public function generateSignRecord($params)
    {
        $agreementNo = ArrayUtil::safeGet($params, 'agreement_no');
        $externalAgreementNo = ArrayUtil::safeGet($params, 'external_agreement_no');

        $externalAgreementNoArr = explode('-', $externalAgreementNo);
        $orderId = $externalAgreementNoArr[0] ?? '';
        $source = $externalAgreementNoArr[1] ?? '';

        // 查询是否有相同的签约号
        $agreementNoModel = AutoRenewalAgreementModelDao::getInstance()->loadAgreement($agreementNo);
        if ($agreementNoModel != null) {
            throw new AlreadyDeliveryException('签约记录已存在', 500);
        }

        $order = OrderModelDao::getInstance()->loadOrder($orderId);
        if ($order == null) {
            throw new FQException('订单不存在', 500);
        }

        // 判断用户和当前协议类型是否存在
        $isAgreement = $this->processVipAgreementStatus($order->userId, $order->type);
        if ($isAgreement) {
            Log::channel(['pay', 'file'])->info(sprintf('AutoPayAlipayService::generateSignRecord already user_id=%s, type=%s',
                $order->userId, $order->type));
            throw new AlreadyDeliveryException('之前已存在过签约记录', 500);
        }

        $signTime = strtotime(ArrayUtil::safeGet($params, 'sign_time'));
        $executeTimeStr = VipService::getInstance()->getVipExecuteTime($signTime);

        $model = new AutoRenewalAgreementModel();
        $model->userId = $order->userId;
        $model->agreementNo = $agreementNo;
        $model->externalAgreementNo = $externalAgreementNo;
        $model->status = VipConstant::AGREEMENT_STATUS_TRUE;
        $model->transactionIds = $orderId;
        $model->signTime = ArrayUtil::safeGet($params, 'sign_time');
        $model->signType = $order->type;
        $model->firstProductId = $order->productId;
        $model->productId = $order->productId;
        $model->renewStatus = 1;  // 1: 等待扣费;  2: 扣费失败
        $model->executeTime = $executeTimeStr;
        $model->outparam = json_encode($params);
        $model->contractSource = 'alipay';
        $model->configSource = $source;

        return AutoRenewalAgreementModelDao::getInstance()->storeModel($model);
    }

    /**
     * @desc 获取最新的签约状态
     * @param $userId
     * @param $type
     * @return bool  true签约中   false表示为签约
     * @throws \Exception
     */
    public function processVipAgreementStatus($userId, $type): bool
    {
        // 是否签约
        $isAgreement = false;
        $agreementModel = AutoRenewalAgreementModelDao::getInstance()->getUserAgreement($userId, $type);
        // 支付宝签约需要到支付宝验证
        if ($agreementModel != null) {
            // 支付宝的用户协议是否正常
            if ($agreementModel->contractSource == 'alipay') {
                $agreementStatus = AlipayService::getInstance()->isUserAgreement($agreementModel->configSource, $agreementModel->agreementNo);
                if ($agreementStatus) {
                    $isAgreement = true;
                } else {
                    // 修改之前协议状态--为已失效状态
                    AutoRenewalAgreementModelDao::getInstance()->updateAgreementStatus($agreementModel->agreementNo, VipConstant::AGREEMENT_STATUS_FALSE);
                }
            }

            if ($agreementModel->contractSource == 'apple') {
                $isAgreement = true;
            }
        }
        return $isAgreement;
    }

    /**
     * @desc 发送到自定义钉钉群
     * @param $sendMsg
     * @return mixed
     */
    public function sendPayDingTalkMsg($sendMsg)
    {
        // 自定义机器人-签名方式验证
        $url = 'https://oapi.dingtalk.com/robot/send?access_token=8e2f03bf037cf331843f4182d7be794fda081beb004a52f2bcc00448db842125';

        // 第一步，把timestamp+"\n"+密钥当做签名字符串，使用HmacSHA256算法计算签名，然后进行Base64 encode，最后再把签名参数再进行urlEncode，得到最终的签名（需要使用UTF-8字符集）。
        $time = time() * 1000;//毫秒级时间戳，我这里为了方便，直接把时间*1000了
        $secret = 'SEC6cfb51c5ea9eedc9a8fb0409dc5c8c900ee5f4a284c3e3b2701e472766c89e5d';
        $sign = hash_hmac('sha256', $time . "\n" . $secret, $secret, true);
        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        $url = "{$url}&timestamp={$time}&sign={$sign}";

        //使用关键字
        //类型1：文本
        $msg1 = [
            'msgtype' => 'text',//这是文件发送类型，可以根据需求调整
            'text' => [
                'content' => $sendMsg,
            ],
        ];

        $data = json_encode($msg1);
        $response = curlData($url, $data, 'POST');

        return json_decode($response, true);
    }

    /**
     * @desc 处理苹果订阅
     * @param $data
     * @return string
     */
    public function handleAppleSubscription($data)
    {
        if (!empty($data)) {
            /*通知类型
             https://developer.apple.com/documentation/appstoreservernotifications/notificationtype
             CONSUMPTION_REQUEST 表示客户针对消耗品内购发起退款申请
             DID_CHANGE_RENEWAL_PREF 对其订阅计划进行了更改 如果subtype是UPGRADE，则用户升级了他们的订阅;如果subtype是DOWNGRADE，则用户将其订阅降级或交叉分级
             DID_CHANGE_RENEWAL_STATUS 通知类型及其subtype指示用户对订阅续订状态进行了更改
             DID_FAIL_TO_RENEW 一种通知类型及其subtype指示订阅由于计费问题而未能续订
             DID_RENEW 一种通知类型，连同其subtype指示订阅成功续订
             EXPIRED 一种通知类型及其subtype指示订阅已过期
             GRACE_PERIOD_EXPIRED 表示计费宽限期已结束，无需续订，因此您可以关闭对服务或内容的访问
             OFFER_REDEEMED 一种通知类型，连同其subtype指示用户兑换了促销优惠或优惠代码。 subtype DID_RENEW
             PRICE_INCREASE 一种通知类型，连同其subtype指示系统已通知用户订阅价格上涨
             REFUND 表示 App Store 成功为消耗性应用内购买、非消耗性应用内购买、自动续订订阅或非续订订阅的交易退款
             REFUND_DECLINED 表示 App Store 拒绝了应用开发者发起的退款请求
             RENEWAL_EXTENDED 表示 App Store 延长了开发者要求的订阅续订日期
             REVOKE表示 用户有权通过家庭共享获得的应用内购买不再通过共享获得
             SUBSCRIBED 一种通知类型，连同其subtype指示用户订阅了产品
             1. 用户主动取消订阅notificationType:DID_CHANGE_RENEWAL_STATUS
             2. 用户取消订阅，又重新开通连续订阅notificationType: SUBSCRIBED  subtype: RESUBSCRIBE
             3. 用户首次开通订阅notificationType: SUBSCRIBED  subtype: INITIAL_BUY
             */
            $notificationType = $data['notificationType'] ?? '';
            $transactionData = $data['signedTransactionInfo'] ?? [];
            $signedRenewalInfo = $data['signedRenewalInfo'] ?? [];

            $appStoreProductId = $transactionData['productId'];
            $subType = $data['subtype'] ?? '';
            $originalTransactionId = $transactionData['originalTransactionId'];  //原始交易ID
            $dealId = ApplePayService::getInstance()->makeDealId($transactionData['transactionId']);  //  苹果订单号
            $signTime = 0;
            if (isset($signedRenewalInfo['signedDate'])){
                $signTime = intval($signedRenewalInfo['signedDate'] / 1000);  // 签约时间
            }
//            $expires_date = date('Y-m-d H:i:s',$transactionData['expiresDate']/1000);
            try {
                // 查询原始交易绑定的用户ID
                $agreementModel = AutoRenewalAgreementModelDao::getInstance()->loadAgreement($originalTransactionId);
                switch ($notificationType) {
                    case in_array($notificationType, ['DID_RENEW','SUBSCRIBED']):  // 自动扣款、首次订阅
                        //开通成功以及续订成功处理交易  1. 创建订单   2. 发货   3. 更新订阅状态
                        if ($agreementModel != null){
                            if ($subType == 'BILLING_RECOVERY'){  // 仅通知恢复回调
                                // 更新订阅状态
                                $upDate = [];
                                $upDate['status'] =  VipConstant::AGREEMENT_STATUS_TRUE;
                                AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
                                return 'SUCCESS_BILLING_RECOVERY';
                            }
                            $userId = $agreementModel->userId;
                            $order = ChargeService::getInstance()->iosBuyProduct($userId, $appStoreProductId, 4, $dealId, true);
                            // 更新订阅状态
                            $upDate = [];
                            $upDate['status'] =  VipConstant::AGREEMENT_STATUS_TRUE;
                            $upDate['transaction_ids'] = sprintf("%s,%s", $agreementModel->transactionIds, $order->orderId);
                            AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
                        } else {
                            $order = OrderModelDao::getInstance()->findOrderByDealId($dealId);
                            if ($order == null){
                                // 苹果回调可能比发货快，订单为空延迟3s
                                sleep(3);
                                $order = OrderModelDao::getInstance()->findOrderByDealId($dealId);
                                if ($order == null){
                                    Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::autoSignAppleNotify not exist order data=%s', json_encode($data)));
                                    // 签约信息不存在、并且订单信息也不存在
                                    throw new FQException('ERROR_ORDER_NOT_EXIST', 500);
                                }
                            }
                            $userId = $order->userId;
                            // 添加订阅
                            $model = new AutoRenewalAgreementModel();
                            $model->userId = $order->userId;
                            $model->agreementNo = $originalTransactionId;
                            $model->externalAgreementNo = $originalTransactionId;
                            $model->status = VipConstant::AGREEMENT_STATUS_TRUE;
                            $model->transactionIds = $order->orderId;
                            $model->signTime = date('Y-m-d H:i:s', $signTime);
                            $model->signType = $order->type;
                            $model->firstProductId = $order->productId;
                            $model->productId = $order->productId;
                            $model->renewStatus = 1;  // 1: 等待扣费;  2: 扣费失败
                            $model->contractSource = 'apple';
                            $model->outparam = json_encode($data);
                            AutoRenewalAgreementModelDao::getInstance()->storeModel($model);
                        }
                        // 发货
                        if ($order->status == OrderStates::$CREATE) {
                            ChargeService::getInstance()->payAndDeliveryOrderByOrder($order, $dealId, $data);
                            Log::info(sprintf('autoSignAppleNotify ok userId=%d dealId=%s appStoreProductId=%s productId=%d',
                                $userId, $dealId, $appStoreProductId, $order->productId));
                        }
                        break;
                    case in_array($notificationType, ['CONSUMPTION_REQUEST', 'REFUND_DECLINED']): // 退款请求,拒绝退款先不处理

                        break;
                    case 'DID_FAIL_TO_RENEW': // 取消订阅
                        // 取消订阅状态
                        $upDate = [];
                        $upDate['status'] =  VipConstant::AGREEMENT_STATUS_FALSE;
                        AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
                        break;
                    case 'DID_CHANGE_RENEWAL_PREF': // 订阅发生了升降级
                        $area = ProductSystem::getInstance()->getArea('ios');
                        $product = $area->findByAppStoreProductId($appStoreProductId);
                        $upDate = [];
                        $upDate['status'] =  VipConstant::AGREEMENT_STATUS_TRUE;
                        $upDate['product_id'] =  $product->productId;
                        AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
                        break;
                    case 'EXPIRED': // 过期
                        $upDate = [];
                        $upDate['status'] =  VipConstant::AGREEMENT_STATUS_EXPIRED;
                        AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
                        break;
                    case 'DID_CHANGE_RENEWAL_STATUS': // 续订状态进行了更改
                        $upDate = [];
                        // 用户重新启用订阅自动续订
                        if ($subType == 'AUTO_RENEW_ENABLED'){
                            $upDate['status'] =  VipConstant::AGREEMENT_STATUS_TRUE;
                        } elseif ($subType == 'AUTO_RENEW_DISABLED') {   //取消订阅成功  用户禁用订阅自动续订，或用户请求退款后 App Store 禁用订阅自动续订
                            $upDate['status'] =  VipConstant::AGREEMENT_STATUS_FALSE;
                        } else {
                            throw new FQException('NOT EXIST DID_CHANGE_RENEWAL_STATUS' . $subType, 500);
                        }
                        AutoRenewalAgreementModelDao::getInstance()->updateAgreement($agreementModel->agreementNo, $upDate);
                        break;
                    case 'REFUND':  // 用户退款处理交易
                        // 退款
                        throw new FQException('REFUND', 500);
                    default:
                        // 未处理到的订阅状态
                        throw new FQException('OTHER_TYPE', 500);
                }
            } catch (\Exception $e) {
                // 有错误消息发送钉钉群
                $data['err_msg'] = 'apple failed - '.$e->getMessage();
                AutoRenewalService::getInstance()->sendPayDingTalkMsg(json_encode($data));
                // 苹果回调通知比较慢，补偿机制
                if (in_array($notificationType, ['SUBSCRIBED', 'DID_CHANGE_RENEWAL_STATUS'])) {
                    $redis = RedisCommon::getInstance()->getRedis();
                    $countKey = sprintf('%s_%s_%s', 'apple', $notificationType, $originalTransactionId);
                    $count = $redis->incr($countKey);
                    $redis->expire($countKey, 86400);
                    if ($count <= 3) {
                        unset($data['err_msg']);
                        $redis->zAdd('auto_subscription_apple_compensate', time() + $count * 60, json_encode($data));
                    }
                }
                Log::error(sprintf('autoSignAppleNotify failed ex=%d:%s', $e->getCode(), $e->getMessage()));
                return 'ERROR';
            }

            return 'SUCCESS';
        }
    }
}