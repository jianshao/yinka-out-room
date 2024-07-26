<?php


namespace app\domain\thirdpay\dinpay;

use app\domain\thirdpay\common\ThreePaymentException;
use app\utils\ArrayUtil;
use think\facade\Log;


class BasePay
{

    public $config = array();

    protected $url = 'https://api.dinpay.com/gateway/api/pay';    //支付网关

    public function __construct($config)
    {
        if (!ArrayUtil::safeGet($config, 'merchant_code')) {
            throw new ThreePaymentException('缺少配置商户号');
        }

        if (!ArrayUtil::safeGet($config, 'notify_url')) {
            throw new ThreePaymentException('缺少回调地址');
        }

        $this->config = $config;
    }

    /**
     * @desc 统一下单接口
     * @param $params
     */
    protected function pay($params)
    {
        $this->checkParams($params);

        $config = $this->config;

        $data = array(
            'service_type' => $params['service_type'],
            'merchant_code' => $config['merchant_code'],
            'sub_merchant_code' => $config['sub_merchant_code'],
            'notify_url' => $config['notify_url'],
            'interface_version' => "V3.1",
            'sign_type' => "RSA-S",
            'order_no' => $params['order_no'],
            'client_ip' => $config['client_ip'],
            'order_time' => date("Y-m-d H:i:s", time()),
            'order_amount' => $params['order_amount'], // 该笔订单的总金额，以元为单位，
            'input_charset' => "UTF-8",
            'product_name' => config('config.pay_subject'),
        );

        if (isset($params['user_id'])) {
            $data['user_id'] = $params['user_id'];
        }

        //获取sign参数
        $sign = $this->sign($data);
        if (!$sign) {
            throw new ThreePaymentException('签名错误');
        }
        $data['sign'] = $sign;
        Log::channel(['pay', 'file'])->info(sprintf('request din::pay before params order_id=%s data=%s',
            $params['order_no'], json_encode($data)));
        $response = self::curl($this->url, $data);
        Log::channel(['pay', 'file'])->info(sprintf('request din::pay after params response=%s', $response));
        $xml = simplexml_load_string($response);
        $json = json_encode($xml);
        return json_decode($json, true);
    }

    /**
     * 检查参数
     * @param $params
     * @throws ThreePaymentException
     */
    public function checkParams($params)
    {
        //检测必填参数
        if (!isset($params['order_amount'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数order_amount");
        }

        if (!isset($params['order_no'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数order_no");
        }

        if (!isset($params['service_type'])) {
            throw new ThreePaymentException("缺少统一支付接口必填参数service_type");
        }

        return true;
    }

    /**
     * @param $params
     * @return string
     */
    public function sign($params)
    {

        $signStr = $this->getSortParams($params);

        $privateKey = '-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBANTih8vMjKPRTIVr
L5pPH7liunbSc7krrh9G7fcM/eblRipgpU5c4ITlq4Ol+xUMJUu29FobYwpOauBY
Kr3u4KKQbupLtL/f20wU/30Fnx42nVomuM7HEIYw2WUGfi3Gbb3AfBjCgmWpiZqK
6LdcYX0Uu+76ze8s+xCBwDOgXIdvAgMBAAECgYEAggbfBKaqXECxaWhfifO8H8Ji
u8KtNjOsxaPQIy6HQmlVovqm3SczZ1jUmrNdmaxydz2HICZMJvZgpyiS6rGl9+dg
Cn/ky71/jpxGx59MdIx+iyr9ICN9iYaG133mF9KEQjFwlinwfDlrEDrf5rjO1LFV
XIu7pFZQVul2hmUk5xkCQQD4YV6Fz2we7Wao3sYz8d7u+D2B7sMMYDP86ZOjOIm/
SIV1lTeR2bjv02F3ef8i20s820T+TNjldrRMfQdP4x+NAkEA22pmrIeoGANW0dMJ
ZT4safL4oMVlREzDroo6Ooei4rkox4RWBgrclsWoPzU6VxlmZJaJP3TDQUo+e9HU
OGgV6wJBAIKtN73O02OyI0DVdBIAPvobQMELjTMFqlR1z2cgZ9hrn0utpf7mPZZv
7+ecF8+O8JakBjiE1dhkC5fyb9Zn+EECQHml+TzojVKa71Svy4K9QMSQ+DWym12N
reQkMPpoXu+StsA/Z6478WcKOSiqKylFJNbZ+0gaRXL6ZcAiaqXHV3cCQQDf/R58
Hc+/fGkG9cTCEHqk5k3cOx0YUQW/0HJmhMLWCU5sqM81JBpnyl8KtCqUW7bldaZ1
k6+EyFES4MygGqGS
-----END PRIVATE KEY-----';

        $merchant_private_key = openssl_get_privatekey($privateKey);

        openssl_sign($signStr, $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);

        $sign = base64_encode($sign_info);

        return $sign;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams($params)
    {
        $buff = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $k != "sign_type" && $v !== "" && !is_array($v)) {
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
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$action);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response=curl_exec($ch);
        //$res=simplexml_load_string($response);
        curl_close($ch);
        return $response;
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


    /**
     * 参数排序
     *
     * @param array $param
     * @return string
     */
    function getSortParams($param = [])
    {
        unset($param['sign_type']);
        unset($param['sign']);
        ksort($param);
        $signstr = '';
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                if ($value == '') {
                    continue;
                }
                $signstr .= $key . '=' . $value . '&';
            }
            $signstr = rtrim($signstr, '&');
        }
        return $signstr;
    }


}