<?php


namespace app\domain\thirdpay\shengpay;

use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 支付基类
 * Class BasePay
 * @package app\domain\thirdpay\shengpay
 */
class BasePay
{
    public $config = array();

    protected $notifyUrl = '';  // 回调地址

    protected $url = 'https://mchapi.shengpay.com/pay/unifiedorderOffline';    //支付网关

    protected $tradeType = '';  //消息类型

    public function __construct($config)
    {
        if (!ArrayUtil::safeGet($config, 'mchId')) {
            throw new ThreePaymentException('缺少配置商户号');
        }

        if (!ArrayUtil::safeGet($config, 'notifyUrl')) {
            throw new ThreePaymentException('缺少回调地址');
        }

        if (!ArrayUtil::safeGet($config, 'publicKey')) {
            throw new ThreePaymentException('缺少RSA公钥');
        }

        if (!ArrayUtil::safeGet($config, 'privateKey')) {
            throw new ThreePaymentException('缺少RSA私钥');
        }

        $this->config = $config;
        $this->notifyUrl = ArrayUtil::safeGet($config, 'notifyUrl');
    }

    /**
     * @desc 统一下单接口
     * @param $params
     */
    protected function pay($params)
    {
        $this->checkParams($params);

        $config = $this->config;
        // 必传字段
        $data = [
            'mchId' => $config['mchId'], // 商户号
            'outTradeNo' => $params['outTradeNo'], // 商户订单号
            'timeExpire' => $params['timeExpire'], // 交易结束时间(格式为yyyyMMddHHmmss)
            'notifyUrl' => $config['notifyUrl'],// 回调地址
            'nonceStr' => self::getNonceStr(),// 随机字符串
            'totalFee' => $params['totalFee'], // 订单总金额(单位分)
            'tradeType' => $this->tradeType, // 支付方式
            'extra' => '', // 支付要素扩展参数
            'body' => $params['body'],// 商品描述
            'currency' => 'CNY', // 标准币种
            'clientIp' => getIP(),// 用户IP(H5支付时请传用户真实地址 ,支持IPV6)
            'signType' => 'RSA', // 签名类型
        ];

        // 同步跳转地址
        if (isset($params['pageUrl'])) {
            $data['pageUrl'] = $params['pageUrl'];
        }

        if (isset($params['extra'])) {
            $data['extra'] = json_encode($params['extra']);
        }

        //获取sign参数
        $sign = $this->sign($data);
        if (!$sign) {
            throw new ThreePaymentException('签名错误');
        }
        $data['sign'] = $sign;
        Log::channel(['pay', 'file'])->info(sprintf('request sheng::pay before params order_id=%s data=%s',
            $params['outTradeNo'], json_encode($data)));
        $response = self::curl($this->url, $data);
        Log::channel(['pay', 'file'])->info(sprintf('request sheng::pay after params response=%s', $response));
        return json_decode($response, true);
    }

    /**
     * 检查参数
     * @param $params
     * @throws ThreePaymentException
     */
    public function checkParams($params)
    {
        //检测必填参数
        if (!isset($params['totalFee'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数totalFee");
        }

        if (!isset($params['outTradeNo'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数outTradeNo");
        }

        if (!isset($params['body'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数body");
        }

        if (!isset($params['timeExpire'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数timeExpire");
        }

        return true;
    }

    /**
     * @param $params
     * @return string
     */
    public function sign($params)
    {
        ksort($params);
        $string = $this->toUrlParams($params);

        $str = chunk_split($this->config['privateKey'], 64, "\n");
        $key = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
        $signature = '';
        if (openssl_sign($string, $signature, $key)) {
            return base64_encode($signature);
        }
        return null;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams($params)
    {
        $buff = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $v !== "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 发起 server 请求
     * @param string $action
     * @param array $params
     * @return mixed
     */
    private static function curl($action, $params)
    {
        $request = json_encode($params, 320);
        $httpHeader = [];
        $ch = curl_init();
        $httpHeader[] = 'Content-Type:Application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_URL, $action);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret = curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
    }

    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 验签
     * @param $params
     * @return bool
     * @throws ThreePaymentException
     */
    public function checkSign($params)
    {
        if ($this->verifySign($params)) {
            //签名正确
            return true;
        }
        throw new ThreePaymentException("签名错误！");
    }

    /**
     * 验证签名
     * @param $params
     * @return false|int
     */
    public function verifySign($params)
    {
        ksort($params);
        $string = $this->toUrlParams($params);

        // 公钥
        $str = $this->config['publicKey'];
        $str = chunk_split($str, 64, "\n");
        $publicKey = "-----BEGIN PUBLIC KEY-----\n$str-----END PUBLIC KEY-----\n";

        // 私钥
        $str = $this->config['privateKey'];
        $binarySignature = "";
        $str = chunk_split($str, 64, "\n");
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
        openssl_sign($string, $binarySignature, $privateKey);

        return openssl_verify($string, $binarySignature, $publicKey);
    }

}