<?php

// This file is auto-generated, don't edit it. Thanks.
namespace app\common;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\SDK\Dypnsapi\V20170525\Dypnsapi;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dypnsapi\V20170525\Models\VerifyMobileRequest;
use think\facade\Log;

/**
 * install commond : composer require alibabacloud/dypnsapi-20170525 1.0.1
 * Class AlibabaCloudCommon
 * @package app\common
 */
class AlibabaCloudCommon
{

    /**
     * 使用AK&SK初始化账号Client
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @return Dypnsapi Client
     */
    public static function createClient($accessKeyId, $accessKeySecret)
    {
        $config = new Config([
            // 您的AccessKey ID
            "accessKeyId" => $accessKeyId,
            // 您的AccessKey Secret
            "accessKeySecret" => $accessKeySecret
        ]);
        // 访问的域名
        $config->endpoint = "dypnsapi.aliyuncs.com";
        return new Dypnsapi($config);
    }

    /**
     * @param $args
     * @return \AlibabaCloud\SDK\Dypnsapi\V20170525\Models\VerifyMobileResponse
     */

    /**
     * @param $args
     * @return array
     *
     * @response
     * {
     * "GateVerifyResultDTO": {
     * "VerifyResult": "PASS",
     * "VerifyId": 121343241
     * },
     * "Message": "请求成功",
     * "RequestId": 8906582,
     * "Code": "OK"
     * }
     */
    public static function VerifyMobile($phone, $accessToken)
    {
        $client = self::createClient(config('config.OSS.ACCESS_KEY_ID'), config('config.OSS.ACCESS_KEY_SECRET'));
        $verifyMobileRequest = new VerifyMobileRequest([
            "accessCode" => $accessToken,
            "phoneNumber" => $phone,
        ]);
        // 复制代码运行请自行打印 API 的返回值
        $responseObj = $client->verifyMobile($verifyMobileRequest);
        //todo log
        return  $responseObj->toMap();
    }
}
