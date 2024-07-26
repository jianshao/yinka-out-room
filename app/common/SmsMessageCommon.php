<?php

namespace app\common;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;
use TencentCloud\Sms\V20210111\SmsClient;
use think\facade\Log;

class SmsMessageCommon
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new SmsMessageCommon();
        }
        return self::$instance;
    }

//    /**
//     * @param $type  string  模版id名称
//     * @param $phone string 手机号
//     * @param $params array 模版参数
//     */
//    public function sendMessage($type, $phone, $params) {
//        $conf = config('config.ALISMS');
//        AlibabaCloud::accessKeyClient($conf['accessKeyId'], $conf['accessSecret'])->regionId('cn-hangzhou')->asDefaultClient();
//        $result = AlibabaCloud::rpc()
//            ->product('Dysmsapi')
//            ->version('2017-05-25')
//            ->action('SendSms')
//            ->method('POST')
//            ->host('dysmsapi.aliyuncs.com')
//            ->options([
//                'query' => [
//                    'RegionId' => $conf['ali_sms_regionId'],
//                    'PhoneNumbers' => $phone,
//                    'SignName' => $conf['ali_sms_signName'],
//                    'TemplateCode' => $conf[$type],
//                    'TemplateParam' => json_encode($params),
//                ],
//            ])
//            ->request();
//        $result = $result->toArray();
//        Log::record("sms--ali--".$phone.'--'.json_encode($result), "info" );
//        return $result;
//    }


    /**
     * @param $type  string  模版id名称
     * @param $phone string 手机号
     * @param $otherParams array 模版参数
     */
    public function sendMessage($type, $phone, $otherParams)
    {
        $conf = config('config.tencent_sms');
        try {
            // 实例化一个认证对象，入参需要传入腾讯云账户 SecretId 和 SecretKey，此处还需注意密钥对的保密
            // 代码泄露可能会导致 SecretId 和 SecretKey 泄露，并威胁账号下所有资源的安全性。以下代码示例仅供参考，建议采用更安全的方式来使用密钥，请参见：https://cloud.tencent.com/document/product/1278/85305
            // 密钥可前往官网控制台 https://console.cloud.tencent.com/cam/capi 进行获取
            $cred = new Credential($conf['ACCESS_KEY_ID'], $conf['ACCESS_KEY_SECRET']);
            // 实例化一个http选项，可选的，没有特殊需求可以跳过
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint($conf['ENDPOINT']);

            // 实例化一个client选项，可选的，没有特殊需求可以跳过
            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            // 实例化要请求产品的client对象,clientProfile是可选的
            $client = new SmsClient($cred, $conf['Region'], $clientProfile);

            // 实例化一个请求对象,每个接口都会对应一个request对象
            $req = new SendSmsRequest();

            $params = array(
                "PhoneNumberSet" => [$phone],
                "SmsSdkAppId" => $conf['SmsSdkAppId'],
                "TemplateId" => $conf['TemplateId'],
                "SignName" => $conf['SignName'],
                "TemplateParamSet" => [$otherParams['code']]
            );
            $req->fromJsonString(json_encode($params));

            // 返回的resp是一个SendSmsResponse的实例，与请求对象对应
            $resp = $client->SendSms($req);

            // 输出json格式的字符串回包
            $result =  json_decode($resp->toJsonString(),true);
            Log::record("_txSmsSend--".$phone.'--'.json_encode($result), "info" );
            return $result;
        }
        catch(TencentCloudSDKException | \Exception $e) {
            Log::error('_txSmsSend:---'.$e->getMessage());
            return rjson([],500,'server error');
        }
    }

}