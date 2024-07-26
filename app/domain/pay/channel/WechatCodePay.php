<?php


namespace app\domain\pay\channel;


use think\facade\Log;
use Yansongda\Pay\Pay;

class WechatCodePay extends WechatBase
{
    public function pay($order)
    {
        $payOrder = $this->initParams($order);
        $result = $this->WechatCode($payOrder);
        $url = $result->code_url;
        return \QRcode::png($url);
    }

    /**
     * 微信二维码支付
     * @param $order
     * @return mixed
     */
    public function WechatCode($order)
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
        return Pay::wechat($config)->scan($order);
    }
}