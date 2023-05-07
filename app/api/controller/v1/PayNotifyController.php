<?php
namespace app\api\controller\v1;

use app\admin\model\GiftModel;
use app\admin\validate\Gift;
use app\common\RedisCommon;
use app\domain\autorenewal\dao\AutoRenewalAgreementModelDao;
use app\domain\autorenewal\model\AutoRenewalAgreementModel;
use app\domain\autorenewal\service\AutoRenewalService;
use app\domain\exceptions\FQException;
use app\domain\pay\AlreadyDeliveryException;
use app\domain\pay\ChargeService;
use app\domain\pay\dao\OrderModelDao;
use app\domain\pay\OrderStates;
use app\domain\pay\ProductSystem;
use app\domain\redpacket\RedPacketService;
use app\domain\vip\constant\VipConstant;
use app\domain\thirdpay\service\ChinaumsPayService;
use \app\facade\RequestAes as Request;
use app\BaseController;
use app\service\ApplePayService;
use app\utils\ArrayUtil;
use think\facade\Log;


class PayNotifyController extends BaseController
{
	//发红包支付宝回调
	public function alipackets()
	{
		$params = Request::param();
		Log::record("app发红包ali回调-----". json_encode($params), "info" );
		$conf = config('config.alipay_yuansheng');
		if (!isset($params['out_trade_no']) ||
            $params['seller_id'] != $conf['PARTNER'] ||
            $params['trade_status'] != 'TRADE_SUCCESS'
        ) {
            echo 'fail';
        }else{
            $dealId = $params['trade_no'];// 支付宝订单号
            $orderNo = $params['out_trade_no'];
            try {
                $order = ChargeService::getInstance()->payAndDeliveryOrder($orderNo, $dealId, $params);
                // 根据orderId查找红包
                RedPacketService::getInstance()->payAndSendRedPacketByOrderId($order->orderId, $dealId);
                echo 'SUCCESS';
            } catch (\Throwable $e) {
                Log::record("app支付宝红包回调改订单失败-----". json_encode($params), "info" );
                file_put_contents("/tmp/app_ali_yuansheng_hongbao_error.log",time().":".json_encode($params)."".PHP_EOL,FILE_APPEND);
            }
        }

	}

    //发红包支付宝回调
    public function alipacketsMua()
    {
        $params = Request::param();
        Log::record("app发红包ali回调-----". json_encode($params), "info" );
        $conf = config('muaconfig.alipay_yuansheng');
        if (!isset($params['out_trade_no']) ||
            $params['seller_id'] != $conf['PARTNER'] ||
            $params['trade_status'] != 'TRADE_SUCCESS'
        ) {
            echo 'fail';
        }else{
            $dealId = $params['trade_no'];// 支付宝订单号
            $orderNo = $params['out_trade_no'];
            try {
                $order = ChargeService::getInstance()->payAndDeliveryOrder($orderNo, $dealId, $params);
                // 根据orderId查找红包
                RedPacketService::getInstance()->payAndSendRedPacketByOrderId($order->orderId, $dealId);
                echo 'SUCCESS';
            } catch (\Throwable $e) {
                Log::record("app支付宝红包回调改订单失败-----". json_encode($params), "info" );
                file_put_contents("/tmp/app_ali_yuansheng_hongbao_error.log",time().":".json_encode($params)."".PHP_EOL,FILE_APPEND);
            }
        }
    }

	//发红包微信回调
	public function wxpackets()
	{
		$paramxml = file_get_contents("php://input");
		$jsonxml = json_encode(simplexml_load_string($paramxml, 'SimpleXMLElement', LIBXML_NOCDATA));
		$params = json_decode($jsonxml, true);
		Log::record("app发红包wx回调-----". json_encode($params), "info" );
		if($params){
			if(isset($params['result_code']) && $params['result_code'] == 'SUCCESS'){
				$dealId = $params['transaction_id'];// weixin订单号
				$orderNo = $params['out_trade_no'];

                try {
                    $order = ChargeService::getInstance()->payAndDeliveryOrder($orderNo, $dealId, $params);
                    // 根据orderId查找红包
                    RedPacketService::getInstance()->payAndSendRedPacketByOrderId($order->orderId, $dealId);
                    echo '<xml>
						  <return_code><![CDATA[SUCCESS]]></return_code>
						  <return_msg><![CDATA[OK]]></return_msg>
						</xml>';
                } catch (\Throwable $e) {
                    Log::record("app微信红包回调改订单失败-----". json_encode($params), "info" );
                    file_put_contents("/tmp/app_wechat_yuansheng_hongbao_error.log",time().":".json_encode($params)."".PHP_EOL,FILE_APPEND);
                }
			}
		}
	}

    //支付宝原生回调
	public function appAliNotify()
	{
		$params = Request::param();
        $paramsStr = json_encode($params);

        Log::info(sprintf('PayNotifyController::appAliNotify params=%s', $paramsStr));

		$conf = config('config.alipay_yuansheng');
		if (!isset($params['out_trade_no']) ||
            $params['seller_id'] != $conf['PARTNER'] ||
            $params['trade_status'] != 'TRADE_SUCCESS'
        ) {
            echo 'fail';
        }else{
            $dealId = $params['trade_no'];// 支付宝订单号
            $orderId = $params['out_trade_no'];
            try {
                ChargeService::getInstance()->payAndDeliveryOrder($orderId, $dealId, $params);
                Log::info(sprintf('PayNotifyController::appAliNotify SUCCESS orderId=%s dealId=%s',
                    $orderId, $dealId));
                echo 'SUCCESS';
            } catch (AlreadyDeliveryException $e) {
                Log::info(sprintf('PayNotifyController::appAliNotify SUCCESS AlreadyDelivery orderId=%s dealId=%s',
                    $orderId, $dealId));
                echo 'SUCCESS';
            } catch (\Throwable $e) {
                Log::error(sprintf('PayNotifyController::appAliNotify params=%s ex=%d:%s trace=%s',
                    $paramsStr, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
                file_put_contents('/tmp/app_ali_yuansheng_error.log',
                    time() . ':' . $paramsStr . '' . PHP_EOL,FILE_APPEND);
            }
        }

	}

    //支付宝原生回调
    public function appAliNotifyMua()
    {
        $params = Request::param();
        $paramsStr = json_encode($params);

        Log::info(sprintf('PayNotifyController::appAliNotify params=%s', $paramsStr));

        $conf = config('muaconfig.alipay_yuansheng');
        if (!isset($params['out_trade_no']) ||
            $params['seller_id'] != $conf['PARTNER'] ||
            $params['trade_status'] != 'TRADE_SUCCESS'
        ) {
            echo 'fail';
        }else{
            $dealId = $params['trade_no'];// 支付宝订单号
            $orderId = $params['out_trade_no'];
            try {
                ChargeService::getInstance()->payAndDeliveryOrder($orderId, $dealId, $params);
                Log::info(sprintf('PayNotifyController::appAliNotify SUCCESS orderId=%s dealId=%s',
                    $orderId, $dealId));
                echo 'SUCCESS';
            } catch (AlreadyDeliveryException $e) {
                Log::info(sprintf('PayNotifyController::appAliNotify SUCCESS AlreadyDelivery orderId=%s dealId=%s',
                    $orderId, $dealId));
                echo 'SUCCESS';
            } catch (\Throwable $e) {
                Log::error(sprintf('PayNotifyController::appAliNotify params=%s ex=%d:%s trace=%s',
                    $paramsStr, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
                file_put_contents('/tmp/app_ali_yuansheng_error.log',
                    time() . ':' . $paramsStr . '' . PHP_EOL,FILE_APPEND);
            }
        }

    }

	//微信原生回调
	public function appWxNotify()
	{
		$paramxml = file_get_contents("php://input");
		$jsonxml = json_encode(simplexml_load_string($paramxml, 'SimpleXMLElement', LIBXML_NOCDATA));
		$params = json_decode($jsonxml, true);
		Log::info(sprintf('PayNotifyController::appWxNotify params=%s', $jsonxml));
		if ($params) {
			if(isset($params['result_code']) && $params['result_code'] == 'SUCCESS'){
				$dealId = $params['transaction_id'];// weixin订单号
				$orderId = $params['out_trade_no'];
                try {
                    ChargeService::getInstance()->payAndDeliveryOrder($orderId, $dealId, $params);
                    echo '<xml>
						  <return_code><![CDATA[SUCCESS]]></return_code>
						  <return_msg><![CDATA[OK]]></return_msg>
						</xml>';
                } catch (AlreadyDeliveryException $e) {
                    Log::info(sprintf('PayNotifyController::appWxNotify SUCCESS AlreadyDelivery orderId=%s dealId=%s',
                        $orderId, $dealId));
                    echo 'SUCCESS';
                } catch (\Throwable $e) {
                    Log::error(sprintf('PayNotifyController::appWxNotify ex=%d:%s params=%s',
                        $e->getCode(), $e->getMessage(), $jsonxml));
                    file_put_contents('/tmp/app_wechat_yuansheng_error.log',
                        time() . ':' . $jsonxml . '' . PHP_EOL, FILE_APPEND);
                }
			}
		}
	}

    /**
     * 三方支付-银联商务回调
     */
    public function chinaumsNotify()
    {
        $params = Request::param(); // 获取回调参数
        $paramsStr = json_encode($params);
//        $paramsStr = '{"msgType":"wx.notify","payTime":"2022-04-21 11:36:22","buyerCashPayAmt":"1","connectSys":"UNIONPAY","sign":"59393711343F419F8200D42A887E669E","merName":"\u97f3\u604b\u8bed\u97f3","mid":"89844014816ABEN","invoiceAmount":"1","settleDate":"2022-04-21","mW":"mqZR","billFunds":"\u73b0\u91d1:1","buyerId":"otdJ_uB3AVLA_E9mksWVdht76WeM","mchntUuid":"2d9081bd8003b043018017c0be64414d","tid":"K4SLDX6A","instMid":"MINIDEFAULT","receiptAmount":"1","couponAmount":"0","cardAttr":"BALANCE","targetOrderId":"4200001321202204212968838353","signType":"MD5","billFundsDesc":"\u73b0\u91d1\u652f\u4ed80.01\u5143\u3002","subBuyerId":"oy5-E5btOxiWK_kaURjPSMF9GBYk","orderDesc":"\u97f3\u604b\u8bed\u97f3","seqId":"26936918907N","merOrderId":"7266test0023899","targetSys":"WXPay","bankInfo":"OTHERS","totalAmount":"1","createTime":"2022-04-21 11:36:17","buyerPayAmount":"1","notifyId":"cf4ca5b2-e653-402d-8f64-7f4644e157ce","subInst":"104000","status":"TRADE_SUCCESS"}';
//        $params = json_decode($params,true);
        Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::chinaumsNotify response=%s', $paramsStr));

        // 验签，是否合法回调
        if (!ChinaumsPayService::getInstance()->verifySign($params)) {
            throw new \Exception('签名验证失败');
        }

        $orderId = $dealId = $status = '';
        if (isset($params['targetOrderId'])) {
            $dealId = ArrayUtil::safeGet($params, 'targetOrderId');// 支付宝订单号
            $orderId = substr(ArrayUtil::safeGet($params, 'merOrderId'), 4);//银联要求merOrderId必须带7266这里我们截取掉
            $status = ArrayUtil::safeGet($params, 'status');
        } elseif (isset($params['billPayment'])) { // 扫码场景
            $payment = json_decode($params['billPayment'], true);
            $dealId = ArrayUtil::safeGet($payment, 'targetOrderId');
            $orderId = substr(ArrayUtil::safeGet($params, 'billNo'), 4);
            $status = ArrayUtil::safeGet($payment, 'status');
        }

        if ($orderId && $dealId && $status == 'TRADE_SUCCESS') {
            try {
                ChargeService::getInstance()->payAndDeliveryOrder($orderId, $dealId, $params);
                Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::chinaumsNotify SUCCESS orderId=%s dealId=%s',
                    $orderId, $dealId));
                echo 'success';
            } catch (AlreadyDeliveryException $e) {
                Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::chinaumsNotify SUCCESS AlreadyDelivery orderId=%s dealId=%s',
                    $orderId, $dealId));
                echo 'success';
            } catch (\Throwable $e) {
                Log::channel(['pay', 'file'])->error(sprintf('PayNotifyController::chinaumsNotify params=%s ex=%d:%s trace=%s',
                    $paramsStr, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
                file_put_contents('/tmp/app_ali_chinaums_error.log',
                    time() . ':' . $paramsStr . '' . PHP_EOL,FILE_APPEND);
                echo 'error1';
            }
        } else {
            Log::channel(['pay', 'file'])->error(sprintf('PayNotifyController::chinaumsNotify1 params=%s', $paramsStr));
            echo 'error';
        }
    }

    /**
     * @desc 支付宝自动续费回调  ( 签约成功有回调，解约没有回调 )
     * @throws \Exception
     */
    public function autoSignAliNotify()
    {
        $params = Request::param(); // 获取回调参数
        $paramsStr = json_encode($params);
        Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::autoSignAliNotify response=%s', $paramsStr));

//        $paramsStr = '{"charset":"utf-8","notify_time":"2022-05-10 21:10:39","alipay_user_id":"2088512960076231","sign":"RGCD0bv\/y5+K+ipZyHITDQsfNW1Hnhgx0ez7dNprpk0EZIJ6XeojVlLhhxkUBVXMBtyRMIAmKCzndj6WsXWwNladY76Cpb+rDmyrj4+cI1yuDAlobVPxx9Q95MF5Ro6Di\/iA847SyFVlOx7GD8C0OLAiMznDrraMJViOsu4ZAZ0l7mLqSi3GOP\/SEE81kc+CXSaOp7oqcEyymptLECydX87zuVLYr5z9ANxkBftvbaOPTLVZv\/oIVc7SSpd1eTS\/p6zQ81xNWQfQxEZtiFvUxkxbSGGsUIAw8foo4F196MIZ8BqTfvCzBs8LJ+o6Zq85PKBgN\/yWKFMl31MVNGGCcw==","external_agreement_no":"1700336165218822912819-config","version":"1.0","sign_time":"2022-05-10 21:10:39","notify_id":"2022051000222211039022101422087419","notify_type":"dut_user_sign","agreement_no":"20225310838635316223","invalid_time":"2115-02-01 00:00:00","auth_app_id":"2021001146649415","personal_product_code":"CYCLE_PAY_AUTH_P","valid_time":"2022-05-10 21:10:39","app_id":"2021001146649415","sign_type":"RSA2","sign_scene":"INDUSTRY|SOCIALIZATION","status":"NORMAL","alipay_logon_id":"157******67"}';
//        $params = json_decode($paramsStr,true);

        if (ArrayUtil::safeGet($params,'status') != 'NORMAL' ||
            !ArrayUtil::safeGet($params,'agreement_no')
        ) {
            return false;
        }

        try {
            AutoRenewalService::getInstance()->generateSignRecord($params);
            Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::autoSignAliNotify SUCCESS params=%s', $paramsStr));
            echo 'SUCCESS';
        } catch (AlreadyDeliveryException $e) {
            Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::autoSignAliNotify SUCCESS agreement already params=%s', $paramsStr));
            echo 'SUCCESS';
        } catch (\Exception $e) {
            Log::channel(['pay', 'file'])->error(sprintf('PayNotifyController::autoSignAliNotify ERROR params=%s ex=%d:%s trace=%s',
                $paramsStr, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            echo 'ERROR';
        }
    }

    /**
     * @desc 苹果订阅回调
     * @throws \Exception
     */
    public function autoSignAppleNotify()
    {
        $params = Request::param(); // 获取回调参数
        if (empty($params)){
            return false;
        }
        $paramsStr = json_encode($params);
        Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::autoSignAppleNotify response=%s', $paramsStr));
        $data = $params['signedPayload'] ?? [];
        $data = $this->verifyToken($data);
        if (!isset($data['data']['signedTransactionInfo'])){
            AutoRenewalService::getInstance()->sendPayDingTalkMsg($paramsStr);
            return false;
        }
        $data['signedTransactionInfo'] = $this->verifyToken($data['data']['signedTransactionInfo']);
        unset($data['data']['signedTransactionInfo']);
        // 订阅信息-可能不存在
        if (isset($data['data']['signedRenewalInfo'])) {
            $data['signedRenewalInfo'] = $this->verifyToken($data['data']['signedRenewalInfo']);
            unset($data['data']['signedRenewalInfo']);
        }
        Log::channel(['pay', 'file'])->info(sprintf('PayNotifyController::autoSignAppleNotify formet data=%s', json_encode($data)));

        // 处理苹果订阅
        return AutoRenewalService::getInstance()->handleAppleSubscription($data);
    }

    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $Token 需要验证的token
     * @return bool|string
     */
    public static function verifyToken($Token)
    {
        $tokens = explode('.', $Token);
        if (count($tokens) != 3)
            return false;

        list($base64header, $base64payload) = $tokens;

        //获取jwt算法
        $base64decodeheader = json_decode(self::base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);
        if (empty($base64decodeheader['alg']) || $base64decodeheader['alg'] != 'ES256')
            return false;

        $payload = json_decode(self::base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);

        return $payload;
    }


    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    private static function base64UrlEncode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    private static function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

}