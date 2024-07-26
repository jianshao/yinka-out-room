<?php


namespace app\domain\thirdpay\shengpay;


use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 微信公共号
 * Class WxGzhPay
 * @package app\domain\thirdpay\shengpay
 */
class WxGzhPay extends BasePay
{
    public $tradeType = 'wx_jsapi';  //消息类型

    protected static $instance;

    /**
     * 下单请求
     * @param $params
     * @return array
     * @throws ThreePaymentException
     */
    public function payMent($params)
    {
        $payInfo = $this->pay($params);
        if (empty($payInfo) || ArrayUtil::safeGet($payInfo, 'returnCode') != 'SUCCESS') {
            Log::channel(['pay', 'file'])->error(sprintf('WxGzhPay::payMent response=%s', json_encode($payInfo)));
            throw new ThreePaymentException("下单支付失败");
        }

        return json_decode(ArrayUtil::safeGet($payInfo, 'payInfo', ''), true);
    }
}