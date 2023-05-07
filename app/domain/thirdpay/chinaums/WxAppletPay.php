<?php


namespace app\domain\thirdpay\chinaums;


use app\domain\autorenewal\service\AutoRenewalService;
use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * @desc 微信小程序下单银联商务
 * Class WxAppletPay
 * @package app\domain\thirdpay\chinaums
 */
class WxAppletPay extends BasePay
{
    public $msgType = 'wx.unifiedOrder';  //消息类型

    protected $jump = false; // 是否重定向

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
        $payInfo = $this->pay($params);
        if (empty($payInfo) ||  ArrayUtil::safeGet($payInfo, 'errCode') != 'SUCCESS') {
            Log::channel(['pay', 'file'])->error(sprintf('WxAppletPay::payMent response=%s', json_encode($payInfo)));
            AutoRenewalService::getInstance()->sendPayDingTalkMsg(json_encode(array_merge($payInfo, $params)));
            throw new ThreePaymentException("下单支付失败");
        }
        $res = [
            'miniPayRequest' => ArrayUtil::safeGet($payInfo, 'miniPayRequest')
        ];
        return $res;
    }

    public function initParams($params)
    {
        $initParams =  [
            'instMid' => 'MINIDEFAULT',
            'tradeType' => 'MINI'
        ];
        return array_merge($params, $initParams);
    }
}