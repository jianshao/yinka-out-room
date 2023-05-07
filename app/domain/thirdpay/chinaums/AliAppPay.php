<?php


namespace app\domain\thirdpay\chinaums;


use app\domain\autorenewal\service\AutoRenewalService;
use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * @desc app支付宝下单银联商务
 * Class AppAliPay
 * @package app\domain\thirdpay\chinaums
 */
class AliAppPay extends BasePay
{
    public $msgType = 'trade.appPreOrder';  //消息类型

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
            Log::channel(['pay', 'file'])->error(sprintf('AliAppPay::payMent response=%s', json_encode($payInfo)));
            AutoRenewalService::getInstance()->sendPayDingTalkMsg(json_encode(array_merge($payInfo, $params)));
            throw new ThreePaymentException("下单支付失败");
        }

        return [
            'appPayRequest' => ArrayUtil::safeGet($payInfo, 'appPayRequest', [])
        ];
    }

    public function initParams($params)
    {
        $initParams = [
            'instMid' => 'APPDEFAULT',
        ];
        return array_merge($params, $initParams);
    }
}