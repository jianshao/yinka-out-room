<?php


namespace app\domain\pay\channel;


use think\facade\Log;
use Yansongda\Pay\Pay;

class AppAlipay extends AlipayBase
{
    public function pay($order, $isRedPackets)
    {
        $payOrder = $this->initParams($order);
        return $this->appAlipay($payOrder, $isRedPackets);
    }

    //app支付宝支付(原生)
    public function appAlipay($order, $isRedpacket = false)
    {
        $conf = config("$this->config.alipay_yuansheng");
        $config = [
            'app_id' => $conf['app_id'],
            'notify_url' => $isRedpacket ? $conf['redpacket_notify_url'] : $conf['notify_url'],
            'return_url' => $conf['return_url'],
            'ali_public_key' => $conf['ali_public_key'],
            // 加密方式： **RSA2**
            'private_key' => $conf['private_key'],
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'debug', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            //'mode' => 'dev',
        ];
        Log::debug(sprintf('PayService::appAlipay payOrder=%s config=%s',
            json_encode($order), json_encode($config)));
        return Pay::alipay($config)->app($order)->getContent();
    }
}