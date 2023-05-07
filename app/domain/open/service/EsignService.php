<?php

namespace app\domain\open\service;

use app\utils\CommonUtil;
use think\facade\Log;

//esign service
class EsignService
{
    protected static $instance;


    private $requestUrl = '';
    protected $baseUrl = '';
    private $fullUrl = '';

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->initLoadBaseUrl();
    }


    private function initLoadBaseUrl()
    {
        if (CommonUtil::getAppDev()) {
            $this->baseUrl = 'https://smlopenapi.esign.cn';
        } else {
            $this->baseUrl = 'https://openapi.esign.cn';
        }
    }

    private function getProjectId()
    {
        return config("config.esign.project_id", "7438922090");
    }

    private function getProjectScert()
    {
        return config("config.esign.project_scert", "3e6da57483d30a45ed8bada010f0ce84");
    }

    /**
     * 构造头部信息
     * @param $contentMD5
     * @param $reqSignature
     * @return array
     */
    public function buildCommHeader($contentMD5, $reqSignature)
    {
        $headers = array(
            'X-Tsign-Open-App-Id:' . $this->getProjectId(),
            'X-Tsign-Open-Ca-Timestamp:' . CommonUtil::getMillisecond(),
            'Accept:*/*',
            'X-Tsign-Open-Ca-Signature:' . $reqSignature,
            'Content-MD5:' . $contentMD5,
            'Content-Type:application/json; charset=UTF-8',
            'X-Tsign-Open-Auth-Mode:Signature'
        );
        return $headers;
    }

    private function setUrl($fullUrl)
    {
        $this->requestUrl = sprintf("%s%s", $this->baseUrl, $fullUrl);
        $this->fullUrl = $fullUrl;
    }

    public function test_bank3Factors()
    {
        $this->setUrl("/v2/identity/verify/individual/bank3Factors");
        $params = [
            'idNo' => "510321199106120019",
            'name' => "胡洋",
            'cardNo' => "6210676862306686101",
        ];

        $paramStr = json_encode($params, JSON_UNESCAPED_SLASHES);
        $contentMd5 = $this->getContentMd5($paramStr);
        $reqSignature = $this->getSignature("POST", "*/*", "application/json; charset=UTF-8", $contentMd5, "", "", $this->fullUrl);
        $headers = $this->buildCommHeader($contentMd5, $reqSignature);
        $result = $this->sendHttp("POST", $this->requestUrl, $headers, $paramStr);
        dd($result);
    }


    /**
     * @info 个人银行卡3要素信息比对
     * @doc https://open.esign.cn/doc/detail?id=opendoc%2Fidentity_service%2Fyp6dhb&namespace=opendoc%2Fidentity_service
     * string(90) "{"code":30500101,"message":"参数错误：请输入正确的身份证号码","data":null}
     * string(88) "{"code":0,"message":"成功","data":{"verifyId":"265b2850-9af9-4500-8e32-3198357a6b57"}}"
     * @param $idNo
     * @param $name
     * @param $cardNo
     * @return bool|string
     */
    public function bank3Factors($idNo, $name, $cardNo)
    {
//        if (CommonUtil::getAppDev()) {
//            return '{"code":0,"message":"成功","data":{"verifyId":"265b2850-9af9-4500-8e32-3198357a6b57"}}';
//        }
        $this->setUrl("/v2/identity/verify/individual/bank3Factors");
        $params = [
            'idNo' => $idNo,
            'name' => $name,
            'cardNo' => $cardNo,
        ];
        $paramStr = json_encode($params, JSON_UNESCAPED_SLASHES);
        $contentMd5 = $this->getContentMd5($paramStr);
        $reqSignature = $this->getSignature("POST", "*/*", "application/json; charset=UTF-8", $contentMd5, "", "", $this->fullUrl);
        $headers = $this->buildCommHeader($contentMd5, $reqSignature);
        return $this->sendHttp("POST", $this->requestUrl, $headers, $paramStr);
    }

    public function writeLog($text)
    {
        if (is_array($text) || is_object($text)) {
            $text = json_encode($text);
        }
        Log::info(sprintf("EsignService_writeLog:%s", $text));
    }


    //常规请求类
    public function sendHttp($reqType = 'GET', $url, $headers = array(), $param = null)
    {

        $logData = [
            'url' => $url,
            'httpMethod' => $reqType,
            'data' => $param,
            'header' => $headers
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $reqType);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $postData = is_array($param) ? json_encode($param, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $param;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        //https request
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_array($headers) && 0 < count($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $curlRes = curl_exec($ch);
        curl_close($ch);

        $logData['result'] = $curlRes;
        $this->writeLog($logData);
        return $curlRes;
    }


    public function getContentMd5($bodyData)
    {
        return base64_encode(md5($bodyData, true));
    }

    /**
     * 生成签名
     * @param $httpMethod
     * @param $accept
     * @param $contentType
     * @param $contentMd5
     * @param $date
     * @param $headers
     * @param $url
     * @return string
     */
    public function getSignature($httpMethod, $accept, $contentType, $contentMd5, $date, $headers, $url)
    {
        $stringToSign = $httpMethod . "\n" . $accept . "\n" . $contentMd5 . "\n" . $contentType . "\n" . $date . "\n" . $headers;
        if ($headers != '') {
            $stringToSign .= "\n" . $url;
        } else {
            $stringToSign .= $url;
        }
        $signature = hash_hmac("sha256", utf8_encode($stringToSign), utf8_encode($this->getProjectScert()), true);
        $signature = base64_encode($signature);
        return $signature;
    }


}