<?php


namespace app\domain\thirdpay\chinaums;

/**
 * @desc 微信公众号下单银联商务
 * Class WxGzhPay
 * @package app\domain\thirdpay\chinaums
 */
class WxGzhPay extends BasePay
{
    public $msgType = 'WXPay.jsPay';  //消息类型

    protected $url = 'https://qr.chinaums.com/netpay-portal/webpay/pay.do';    //支付网关

    protected $jump = true; // 后端直接重定向

    protected static $instance;

    /**
     * @desc 下单请求
     * @param $params
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function payMent($params)
    {
        $params = $this->initParams($params);
        $this->pay($params);
    }

    public function initParams($params)
    {
        $initParams =  [
            'instMid' => 'YUEDANDEFAULT',
            'returnUrl' => config("config.chinaaums.return_gzh_url"),
        ];
        return array_merge($params, $initParams);
    }
}