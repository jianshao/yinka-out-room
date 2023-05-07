<?php

namespace app\common;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
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

    /**
     * @param $type  string  模版id名称
     * @param $phone string 手机号
     * @param $params array 模版参数
     */
    public function sendMessage($type, $phone, $params) {
        $conf = config('config.ALISMS');
        AlibabaCloud::accessKeyClient($conf['accessKeyId'], $conf['accessSecret'])->regionId('cn-hangzhou')->asDefaultClient();
        $result = AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->version('2017-05-25')
            ->action('SendSms')
            ->method('POST')
            ->host('dysmsapi.aliyuncs.com')
            ->options([
                'query' => [
                    'RegionId' => $conf['ali_sms_regionId'],
                    'PhoneNumbers' => $phone,
                    'SignName' => $conf['ali_sms_signName'],
                    'TemplateCode' => $conf[$type],
                    'TemplateParam' => json_encode($params),
                ],
            ])
            ->request();
        $result = $result->toArray();
        Log::record("sms--ali--".$phone.'--'.json_encode($result), "info" );
        return $result;
    }


}