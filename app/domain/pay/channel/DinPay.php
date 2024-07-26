<?php


namespace app\domain\pay\channel;

use app\domain\thirdpay\service\DingPayService;

class DinPay
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
        return [
            'order_no' => $order->orderId,
            'order_amount' => $order->rmb,
        ];
    }


    public function wxH5Pay($order)
    {
        $payOrder = $this->initParams($order);
        return DingPayService::getInstance()->wxH5Pay($payOrder);
    }

}