<?php


namespace app\domain\thirdpay\service;

use app\domain\thirdpay\chinaums\AliAppPay;
use app\domain\thirdpay\chinaums\AliH5Pay;
use app\domain\thirdpay\chinaums\BasePay;
use app\domain\thirdpay\chinaums\CtoBPay;
use app\domain\thirdpay\chinaums\WxAppletPay;
use app\domain\thirdpay\chinaums\WxGzhPay;
use app\utils\ArrayUtil;

/**
 * @desc 银联商务 操作类
 * Class ChinaumsPayService
 * @package app\domain\thirdpay\service
 */
class ChinaumsPayService
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
        $midInfo = $this->getMidTid();

        $this->config = array(
            'msgSrc' => config("config.chinaaums.msgSrc"),            // 消息来源(msgSrc)
            'msgSrcId' => config("config.chinaaums.msgSrcId"),        // 来源编号（msgSrcId）
            'mdKey' => config("config.chinaaums.mdKey"),            // 通讯密钥
            'mid' => ArrayUtil::safeGet($midInfo, 'mid', config("config.chinaaums.mid")),      // 商户编号
            'tid' => ArrayUtil::safeGet($midInfo, 'tid', config("config.chinaaums.tid")),     // 终端号
            'notifyUrl' => config("config.chinaaums.notify_url"), // 回调地址
        );
    }

    /**
     * @desc 获取随机商户
     * @return mixed
     */
    private function getMidTid()
    {
        $midList = config("config.chinaaums.mid_list");
        $rand = array_rand($midList);
        return $midList[$rand] ?? [];
    }

    /**
     * @desc 微信小程序支付
     * @param $data
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function weChatAppletPay($data)
    {
        $threePay = new WxAppletPay($this->config);
        return $threePay->payMent($data);
    }

    /**
     * @desc 安卓支付宝支付
     * @param $data
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function aliAppPay($data)
    {
        $threePay = new AliAppPay($this->config);
        return $threePay->payMent($data);
    }

    /**
     * @desc 微信公众号支付
     * @param $data
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function weChatGzhPay($data)
    {
        $threePay = new WxGzhPay($this->config);
        return $threePay->payMent($data);
    }

    /**
     * @desc 阿里H5支付
     * @param $data
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function aliH5Pay($data)
    {
        $threePay = new AliH5Pay($this->config);
        return $threePay->payMent($data);
    }

    /**
     * @desc 银联商务聚合码
     * @param $data
     * @return array
     * @throws \app\domain\thirdpay\common\ThreePaymentException
     */
    public function ctoBPay($data)
    {
        $threePay = new CtoBPay($this->config);
        return $threePay->payMent($data);
    }

    /**
     * @desc 验签 md5方式
     * @param $data
     * @return bool
     */
    public function verifySign($data)
    {
        //返回参数生成sign
        $sign = $this->generateSign($data);
        //返回的sign
        $returnSign = $data['sign'];
        if ($returnSign != $sign) {
            return false;
        }

        return true;
    }

    /**
     * 根绝类型生成sign
     * @param $params
     * @param string $signType
     * @return string
     */
    protected function generateSign($params)
    {
        return $this->sign($this->getSignContent($params));
    }

    /**
     * 生成签名
     * @param $data
     * @param string $signType
     * @return string
     */
    protected function sign($data)
    {
        $sign = md5(trim($data));
        return strtoupper($sign);
    }

    /**
     * 生成signString
     * @param $params
     * @return string
     */
    protected function getSignContent($params)
    {
        //sign不参与计算
        $params['sign'] = '';

        //排序
        ksort($params);

        $paramsToBeSigned = [];
        foreach ($params as $k => $v) {
            if (is_array($params[$k])) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            } else if (trim($v) == "") {
                continue;
            }
            $paramsToBeSigned[] = $k . '=' . $v;
        }
        unset ($k, $v);
        //签名字符串
        $stringToBeSigned = (implode('&', $paramsToBeSigned));
        //str_replace('¬','&not',$stringToBeSigned);
        $stringToBeSigned .= ArrayUtil::safeGet($this->config, 'mdKey');
        return $stringToBeSigned;
    }
}