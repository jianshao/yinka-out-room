<?php


namespace app\domain\pay\channel;


class WechatBase
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
        $time_expire = config('config.orderExpire');
        $time = time();
        return [
            'out_trade_no' => $order->orderId,
            'total_fee' => $order->rmb * 100,
            'body' => config('config.pay_subject'),
            'time_expire' => date('YmdHis', $time + $time_expire),
        ];
    }
}