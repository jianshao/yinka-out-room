<?php


namespace app\domain\thirdpay\chinaums;


use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * @desc C扫B聚合下单银联商务
 * Class AppAliPay
 * @package app\domain\thirdpay\chinaums
 */
class CtoBPay extends BasePay
{
    public $msgType = 'bills.getQRCode';  //消息类型

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
        if (empty($payInfo) || ArrayUtil::safeGet($payInfo, 'errCode') != 'SUCCESS') {
            Log::channel(['pay', 'file'])->error(sprintf('CtoBPay::payMent response=%s', json_encode($payInfo)));
            throw new ThreePaymentException("下单支付失败");
        }

        return [
            'billQRCode' => ArrayUtil::safeGet($payInfo, 'billQRCode')
        ];
    }

    public function initParams($params)
    {
        $initParams = [
            'instMid' => 'QRPAYDEFAULT',
            'returnUrl' => config("config.chinaaums.return_web_url"),
        ];
        return array_merge($params, $initParams);
    }
}