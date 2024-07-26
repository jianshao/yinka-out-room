<?php


namespace app\domain\pay\channel;

use app\domain\thirdpay\service\ShengPayService;

class ShengPay
{
    protected $config;

    protected $code;

    public function __construct($config, $code)
    {
        $this->config = $config;
        $this->code = $code;
    }

    public function initParams($order)
    {
        $time = time();
        $timeExpire = config('config.orderExpire');
        return [
            'outTradeNo' => $order->orderId,
            'totalFee' => $order->rmb * 100,
            'body' => config('config.pay_subject'),
            'timeExpire' => date('YmdHis', $time + $timeExpire),
        ];
    }

    /**
     * 微信小程序支付
     * @param $order
     * @return array
     * @throws \app\domain\exceptions\FQException
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function wxAppletPay($order)
    {
        $payOrder = $this->initParams($order);
        return ShengPayService::getInstance()->wxAppletPay($payOrder, $this->code);
    }

    /**
     * 微信公众号支付
     * @param $order
     * @return array
     * @throws \app\domain\exceptions\FQException
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function wxGzhPay($order)
    {
        $payOrder = $this->initParams($order);
        return ShengPayService::getInstance()->wxGzhPay($payOrder, $this->code);
    }

    /**
     * 微信扫码支付
     * @param $order
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function wxScanPay($order)
    {
        $payOrder = $this->initParams($order);
        return ShengPayService::getInstance()->wxScanPay($payOrder);
    }
}