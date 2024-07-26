<?php


namespace app\domain\pay\channel;


use think\facade\Log;
use Yansongda\Pay\Pay;

class AppWechatPay extends WechatBase
{
    public function pay($order, $isRedPackets)
    {
        $payOrder = $this->initParams($order);
        $result = $this->appWechatPay($payOrder, $isRedPackets);
        return json_decode($result, true);
    }

    //app微信支付（原生）
    public function appWechatPay($order, $isRedpacket = false)
    {
        $conf = config("$this->config.wechat_yuansheng");
        $config = [
            'appid' => $conf['appid'],
            'app_id' => $conf['app_id'], // 公众号 APPID
            'miniapp_id' => '', // 小程序 APPID
            'mch_id' => $conf['mch_id'],
            'key' => $conf['key'],
            'notify_url' => $isRedpacket ? $conf['redpacket_notify_url'] : $conf['notify_url'],
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
        Log::debug(sprintf('PayService::appWechatPay payOrder=%s config=%s',
            json_encode($order), json_encode($config)));
        return Pay::wechat($config)->app($order)->getContent();
    }
}