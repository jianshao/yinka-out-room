<?php


namespace app\domain\pay\channel;


use think\facade\Log;
use Yansongda\Pay\Pay;

class AlipayH5 extends AlipayBase
{
    public function pay($order)
    {
        $payOrder = $this->initParams($order);
        return $this->AlipayH5($payOrder);
    }

    /**
     * 支付宝H5支付
     * @param $order
     * @return mixed
     */
    public function AlipayH5($order)
    {
        $conf = config('config.alipay_yuansheng');
        $config = [
            'app_id' => $conf['app_id'],
            'notify_url' => $conf['notify_url'],
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
        return Pay::alipay($config)->wap($order)->getContent();
    }
}