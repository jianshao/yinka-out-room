<?php
namespace app\common;

use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Member\Identification\Models\IdentityParam;
use Alipay\EasySDK\Member\Identification\Models\MerchantConfig;
use think\facade\Log;

//1. 设置参数（全局只需设置一次）Factory::setOptions(getOptions());

class AliPayEasyCommon
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AliPayEasyCommon();
        }
        return self::$instance;
    }

    /**
     * 参数初始化
     */
    public function __construct()
    {
        Factory::setOptions($this->getOptions());
    }

    /**
     * 身份认证初始化
     * @param string $outer_order_no   订单号
     * @param $certName         身份证姓名
     * @param $certNo           身份证号
     * @param $channel          请求渠道
     * @param $biz_code         客户端sdk请求返回值
     */
    public function init($outer_order_no, $certName, $certNo, $channel, $biz_code, $config)
    {
        $identityParam = new IdentityParam();
        $identityParam->identityType = "CERT_INFO";
        $identityParam->certType = "IDENTITY_CARD";
        $identityParam->certName = $certName;
        $identityParam->certNo = $certNo;
        $merchantConfig = new MerchantConfig();
        if($channel == 'appStore') {
            $merchantConfig->returnUrl = $config == 'muaconfig' ? 'mua://com.ali.verify'  : "yinka://com.ali.verify";   //ios回跳地址
        } else {
            $merchantConfig->returnUrl = "";   //安卓回跳地址
        }
        if(!$biz_code) {
            $biz_code = "FACE";
        }
        $result = Factory::member()->identification()->init($outer_order_no, $biz_code, $identityParam, $merchantConfig);
        Log::record("alipay_user_certify_open_init_response--".json_encode($result), "info" );
        //3. 处理响应或异常
        if (!empty($result->code) && $result->code == 10000) {
            return $result->certifyId;
        }
    }

    /**
     * 身份认证开始认证
     * @param $certifyId
     * @return mixed
     */
    public function certify($certifyId)
    {
        $result = Factory::member()->identification()->certify($certifyId);
        Log::record("url--".$result->body, "info" );
        return $result->body;
    }

    public function query($certifyId) {
        $result = Factory::member()->identification()->query($certifyId);
        Log::record("alipay_user_certify_open_query_response--".json_encode($result), "info" );
        if (!empty($result->code) && $result->code == 10000) {
            return $result;
        }
    }

    function getOptions()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';
        $options->signType = 'RSA2';

        $options->appId = config('config.alipay_yuansheng.app_id');   //请填写您的AppId，例如：2019022663440152

        // 为避免私钥随源码泄露，推荐从文件中读取私钥字符串而不是写入源码中
        $options->merchantPrivateKey = config('config.alipay_yuansheng.private_key');   //请填写您的应用私钥，例如：MIIEvQIBADANB ... ...

//        $options->alipayCertPath = '<-- 请填写您的支付宝公钥证书文件路径，例如：/foo/alipayCertPublicKey_RSA2.crt -->';
//        $options->alipayRootCertPath = '<-- 请填写您的支付宝根证书文件路径，例如：/foo/alipayRootCert.crt" -->';
//        $options->merchantCertPath = '<-- 请填写您的应用公钥证书文件路径，例如：/foo/appCertPublicKey_2019051064521003.crt -->';

        //注：如果采用非证书模式，则无需赋值上面的三个证书路径，改为赋值如下的支付宝公钥字符串即可
         $options->alipayPublicKey = config('config.alipay_yuansheng.ali_public_key'); //请填写您的支付宝公钥，例如：MIIBIjANBg...
//         $options->alipayPublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtGFfhjmxZhJkwVtDuqFc8c7x16tDvudtHKgQfJkJE7ZLk2+yyL/U6uNTEN7Nn6X2bsH+xiqLT8AC69qkudLNOCB28dskBwPAHIO5VZ0wQYUNVH2RAZFMfkVTnlUBfssSlRjT9XjBPEKAFFVRDpg4uy1mNp8y52UoBn+jL24L1x9DUT3HVKfRBtTxcbgz55QhIbQ9xd5DijuNfh1oRIhqvdM/zGC0fRyFpWk/9MbgwwFQOi3atG3jS8po7i9Vps8i5PqNGAyu9UT7HffNBPJ3KMK2RFP0rJQTLb0mvwRNgQoFI7dNZNueZfMaVd2v+iuWZ560YxFLqzt+EV9TxSTtxwIDAQAB"; //请填写您的支付宝公钥，例如：MIIBIjANBg...
        //可设置异步通知接收服务地址（可选）
//        $options->notifyUrl = "";   //请填写您的支付类接口异步通知接收服务地址，例如：https://www.test.com/callback

        //可设置AES密钥，调用AES加解密相关接口时需要（可选）
//        $options->encryptKey = "";  //请填写您的AES密钥，例如：aa4BtZ4tspm2wnXLb1ThQA==
//        print_r($options);die;
        return $options;
    }
}