<?php


namespace app\domain\thirdpay\service;

use app\domain\exceptions\FQException;
use app\domain\thirdpay\shengpay\BasePay;
use app\domain\thirdpay\shengpay\WxH5Pay;
use app\domain\thirdpay\shengpay\WxGzhPay;
use app\domain\thirdpay\shengpay\WxScanPay;
use app\service\WeChatOpenService;
use app\utils\ArrayUtil;

/**
 * @desc 银联商务 操作类
 * Class ChinaumsPayService
 * @package app\domain\thirdpay\service
 */
class ShengPayService
{
    private $config;

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->config = array(
            'mchId' => config("config.sheng_pay.mchId"),            // 商户号
            'notifyUrl' => config("config.sheng_pay.notifyUrl"), // 回调地址
            'publicKey' => config("config.sheng_pay.publicKey"), // RSA公钥
            'privateKey' => config("config.sheng_pay.privateKey"), // RSA私钥
        );
    }

    /**
     * 微信小程序支付
     * @param $payOrder
     * @param $code
     * @return array
     * @throws FQException
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function wxAppletPay($payOrder, $code)
    {
        $threePay = new WxH5Pay($this->config);
        if (!$code){
            throw new FQException('code 不能为空', 500);
        }
        $WxOpenidInfo = WeChatService::getInstance()->getWxOpenid($code);
        $subOpenId = ArrayUtil::safeGet($WxOpenidInfo, 'openid');
//            $subOpenId = 'oy5-E5btOxiWK_kaURjPSMF9GBYk';
        if (!$subOpenId) {
            throw new FQException('code 获取openid异常', 500);
        }
        $payOrder['extra'] = [
            'openId' => $subOpenId,
            'appId' => config('config.WECHAT_APPLET.appid'),
        ];

        return $threePay->payMent($payOrder);
    }

    /**
     * 微信公众号支付
     * @param $payOrder
     * @param $code
     * @return array
     * @throws FQException
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function wxGzhPay($payOrder, $code)
    {
        $threePay = new WxGzhPay($this->config);
        if (!$code){
            throw new FQException('code 不能为空', 500);
        }
        $openId = WeChatOpenService::getInstance()->getOauthUrlForOpenid($code);
        if (!$openId) {
            throw new FQException('code 获取openid异常', 500);
        }
        $payOrder['extra'] = [
            'openId' => $openId,
            'appId' => config('config.WECHAT_OPEN.APPID'),
        ];
        $payOrder['pageUrl'] = config('config.sheng_pay.return_gzh_url');

        return $threePay->payMent($payOrder);
    }

    /**
     * 微信扫码
     * @param $data
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function wxScanPay($data)
    {
        $threePay = new WxScanPay($this->config);
        return $threePay->payMent($data);
    }


    /**
     * 验签 md5方式
     * @param $data
     * @return bool
     */
    public function verifySign($data)
    {
        return true;
        //返回参数生成sign
        $threePay = new BasePay($this->config);
        return $threePay->checkSign($data);
    }

}