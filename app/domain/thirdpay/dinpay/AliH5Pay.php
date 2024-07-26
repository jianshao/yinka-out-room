<?php


namespace app\domain\thirdpay\shengpay;


use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 微信小程序下单
 * Class WxAppPay
 * @package app\domain\thirdpay\shengpay
 */
class AliH5Pay extends BasePay
{
    public $tradeType = 'wx_lite';  //消息类型

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
            Log::channel(['pay', 'file'])->error(sprintf('WxAppletPay::payMent response=%s', json_encode($payInfo)));
            throw new ThreePaymentException("下单支付失败");
        }

        return [
            'miniPayRequest' => json_decode(ArrayUtil::safeGet($payInfo, 'payInfo', []), true),
        ];
    }
}