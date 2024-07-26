<?php


namespace app\domain\thirdpay\service;

use app\domain\exceptions\FQException;
use app\domain\thirdpay\dinpay\BasePay;
use app\domain\thirdpay\dinpay\WxH5Pay;
use app\utils\ArrayUtil;


class DingPayService
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
            'merchant_code' => config("config.din_pay.merchant_code"),      // 商户签约时，智付支付平台分配的唯一商家号,举例：1111110106。
            'sub_merchant_code' => config("config.din_pay.sub_merchant_code"),      // 商户签约时，智付支付平台分配的唯一商家号,举例：1111110106。
            'notify_url' => config("config.din_pay.notify_url"), // 回调地址
            'client_ip' => config("config.din_pay.client_ip"), // 回调地址
        );
    }

    public function wxH5Pay($payOrder)
    {
        $threePay = new WxH5Pay($this->config);

        return $threePay->payMent($payOrder);
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