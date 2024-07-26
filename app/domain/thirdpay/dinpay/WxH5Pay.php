<?php


namespace app\domain\thirdpay\dinpay;


use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 微信小程序下单
 * Class WxAppPay
 * @package app\domain\thirdpay\shengpay
 */
class WxH5Pay extends BasePay
{
    public $serviceType = 'weixin_h5_pay';  //消息类型

    protected static $instance;

    /**
     * 下单请求
     * @param $params
     * @return array
     * @throws ThreePaymentException
     */
    public function payMent($params)
    {
        $params['service_type'] = $this->serviceType;
        $payInfo = $this->pay($params);
        $payInfo = $payInfo['response'] ?? [];
        if (empty($payInfo) || ArrayUtil::safeGet($payInfo, 'is_success') != 'T') {
            Log::channel(['pay', 'file'])->error(sprintf('weixin_h5_pay::payMent response=%s', json_encode($payInfo)));
            throw new ThreePaymentException("下单支付失败");
        }

        return [
            'response' => $payInfo,
        ];
    }
}