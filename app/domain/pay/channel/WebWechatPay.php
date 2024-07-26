<?php


namespace app\domain\pay\channel;


use think\facade\Log;
use Yansongda\Pay\Pay;

class WebWechatPay extends WechatBase
{
    public function pay($order)
    {
        $payOrder = $this->initParams($order);
        return $this->webWechatPay($payOrder);
    }

    //web微信支付（原生）
    public function webWechatPay($order)
    {
        $conf = config('config.wechat_yuansheng');
        $config = [
            'appid' => $conf['appid'],
            'app_id' => $conf['app_id'], // 公众号 APPID
            'miniapp_id' => '', // 小程序 APPID
            'mch_id' => $conf['mch_id'],
            'key' => $conf['key'],
            'notify_url' => $conf['notify_url'],
            'cert_client' => '',
            'cert_key' => '',
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'info', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            // 'mode' => 'dev',
        ];
        $response = Pay::wechat($config)->wap($order);
        return $response->getContent();
    }
}