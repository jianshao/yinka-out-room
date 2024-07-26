<?php

namespace app\middleware;

use app\domain\exceptions\FQException;
use app\utils\Aes;
use app\utils\ApiAuth;
use app\utils\RequestHeaders;
use think\facade\App;
use think\facade\Log;

// 记录请求返回值所有信息到日志
class BaseAesLog
{
    private $requestHash;
    private $filterCode = 511;
    private $token = "";

    public function handle($request, \Closure $next)
    {
        $aesDriver = $this->initAesSatus();
        if (!$aesDriver) {
            return $this->originRequest($request, $next);
        }
        try {
            return $this->encryptRequest($request, $next);
        } catch (FQException $e) {
            if ($e->getCode() === $this->filterCode) {
                return $this->originRequest($request, $next);
            }
            throw $e;
        }
    }

    private function initAesSatus()
    {
        $driver = config("config.EncryptDriver");
        if ($driver === "enable") {
            return true;
        }
    }

    /**
     * @param $request
     * @return RequestHeaders
     */
    private function loadRequestHeader($request)
    {
        $headersData = array_change_key_case($request->header());
        $requestHeaders = new RequestHeaders();
        return $requestHeaders->dataToModel($headersData);
    }

    /**
     *
     * @param $request
     * @param \Closure $next
     * @return mixed
     * @throws \Exception
     */
    private function encryptRequest($request, \Closure $next)
    {
//        $this->requestHash = generateToken("requestHash");
        $requestHeaders = $this->loadRequestHeader($request);
        $Aes = new Aes();
//        监测该版本是否开启aes
        $enable = $Aes->isEnableAes($requestHeaders);
        if ($enable === false) {
            throw new FQException("encryptRequest reqeust off", $this->filterCode);
        }
//        获取该版本的aeskey并更新
        $Aes->resetAesKey($requestHeaders);
//        对比request加密配置
        if ($requestHeaders->encrypt !== "true") {
            throw new FQException("未知错误11002", 2);
        }
//        解析param
        $params = $this->getParam($request, $Aes);
        if (empty($params)) {
            throw new FQException("未知错误800", 500);
        }
        $this->WriteBeforeParam($request->url(), $params);
        ApiAuth::getInstance()->authTimestamp($params);
        ApiAuth::getInstance()->authSign($params, $requestHeaders);
        $this->setParam($request, $params);
        $this->setHeader($request, $params);
        $response = $next($request);
        $payload = $this->payload($response->getContent());
//        $this->WriteAfterRawResponse($payload);//本地debug
        $respnseData = $this->encodeAesData($payload, $Aes);
        $response->content($respnseData);
        $this->WriteAfterResponse($response->getContent());
        $this->WriteRunTime();
        return $response;
    }

    /**
     * @param $payloadObject
     * @param $Aes
     * @return false|string
     */
    private function encodeAesData($payloadObject, $Aes)
    {
        if (isset($payloadObject->sensorsData)) {
            unset($payloadObject->sensorsData);
        }
        if (isset($payloadObject->data)) {
            $jsonData = json_encode($payloadObject->data);
            $payloadObject->data = $Aes->aesEncrypt($jsonData);
        }
        return json_encode($payloadObject);
    }

    /**
     * @param $content
     * @return array|mixed
     */
    private function payload($content)
    {
        if (empty($content)) {
            return [];
        }
        return json_decode($content);
    }

    /**
     * @param $request
     * @return array
     * @throws \Exception
     */
    private function getParam($request, $Aes)
    {
        $result = [];
        $param = $request->param('data', "");
        if (empty($param)) {
            return $result;
        }
        $origin = $Aes->aesDecrypt($param);
        parse_str($origin, $result);
        $this->token = $result['token'] ?? "";
        return $result;
    }

    /**
     * @param $request
     * @param $params
     */
    private function setParam($request, $params)
    {
        if (empty($params)) {
            return;
        }
        $request->withMiddleware($params);
    }

    /**
     * @param $request
     * @param $params
     */
    private function setHeader($request, $params)
    {
        $token = $params['token'] ?? "";
        if (empty($token)) {
            return;
        }
        $originHeader = $request->header();
        $originHeader['token'] = $token;
        $request->withHeader($originHeader);
    }

    /**
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    private function originRequest($request, \Closure $next)
    {
//        $this->requestHash = generateToken("requestHash");
        $requestHeaders = $this->loadRequestHeader($request);
        $params = $this->getOriginParam($request);
        $this->WriteBeforeParam($request->url(), $params);
        $this->setParam($request, $params);
        $this->setHeader($request, $params);
//        if ($requestHeaders->encrypt !== "false") {
//            throw new FQException("未知错误11001", 1);
//        }

        $response = $next($request);
//        $this->WriteAfterResponse($response->getContent());
//        $this->WriteRunTime();
        return $response;
    }

    /**
     * @param $request
     * @return array
     * @throws \Exception
     */
    private function getOriginParam($request)
    {
        $result = [];
        $param = $request->param(false);
        if (empty($param)) {
            return $result;
        }
        return $param;
    }

    /**
     * @param $requestLink
     * @param $requestParam
     */
    private function WriteBeforeParam($requestLink, $requestParam)
    {
        Log::info(sprintf('WriteBeforeParam--%s--link:%s--param:%s', $this->token, $requestLink, json_encode($requestParam)));
    }

    /**
     * @param array $content
     */
    private function WriteAfterRawResponse($content)
    {
        Log::info(sprintf('WriteAfterRawResponse--%s--param:%s', $this->token, json_encode($content)));
    }

    /**
     * @param $content
     */
    private function WriteAfterResponse($content)
    {
        Log::info(sprintf('WriteAfterResponse--%s--param:%s', $this->token, $content));
    }

    private function WriteRunTime()
    {
        $runtime = number_format(microtime(true) - App::getBeginTime(), 10, '.', '');
        Log::info('运行时间:' . $runtime);
    }

}
