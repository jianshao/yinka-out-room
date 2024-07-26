<?php


namespace app\domain\pay\channel;


class AlipayBase
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
        $timeExpire = config('config.orderExpire');
        $minute = intval($timeExpire / 60);
        return [
            'out_trade_no' => $order->orderId,
            'total_amount' => $order->rmb,
            'subject' => config('config.pay_subject'),
            'timeout_express' => $minute . 'm'
        ];
    }
}