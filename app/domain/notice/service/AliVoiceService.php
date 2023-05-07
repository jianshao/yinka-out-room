<?php


namespace app\domain\notice\service;


//use AlibabaCloud\SDK\Dyvmsapi\V20170525\Dyvmsapi;
use Darabonba\OpenApi\Models\Config;
//use AlibabaCloud\SDK\Dyvmsapi\V20170525\Models\SingleCallByVoiceRequest;

class AliVoiceService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AliVoiceService();
        }
        return self::$instance;
    }


    /**
     * 使用AK&SK初始化账号Client
     * @param string $accessKeyId
     * @param string $accessKeySecret
     * @return Dyvmsapi Client
     */
    public static function createClient($accessKeyId, $accessKeySecret)
    {
//        $config = new Config([
//            // 您的AccessKey ID
//            "accessKeyId" => $accessKeyId,
//            // 您的AccessKey Secret
//            "accessKeySecret" => $accessKeySecret
//        ]);
//        // 访问的域名
//        $config->endpoint = "dyvmsapi.aliyuncs.com";
//        return new Dyvmsapi($config);
    }

    /**
     * @param string[] $args
     * @return void
     */
    public static function main($args)
    {
//        $client = self::createClient("accessKeyId", "accessKeySecret");
//        $singleCallByVoiceRequest = new SingleCallByVoiceRequest([
//            "calledShowNumber" => "15"
//        ]);
//        // 复制代码运行请自行打印 API 的返回值
//        $client->singleCallByVoice($singleCallByVoiceRequest);
    }

}