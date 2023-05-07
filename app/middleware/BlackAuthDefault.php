<?php

namespace app\middleware;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\form\ClientInfo;
use app\utils\RequestOrm;
use think\Exception;
use think\facade\Log;
use think\facade\Request;


//ip user 身份证号，token，设备id，黑名单校验
class BlackAuthDefault
{
    private $requestHash;

    public function handle($request, \Closure $next)
    {
        try {
            $this->fitHandle($request);
            return $next($request);
        } catch (\Exception $e) {
            return rjsonFit([], $e->getCode(), $e->getMessage());
        }
    }

    private function fitHandle()
    {
        $this->requestHash = generateToken("blackauth");
        $token = Request::header('token', "");
//        校验黑名单
        $this->authBlack($token);
        return true;
    }

//    auth cache
    private function authBlack($token)
    {
        if (empty($token)) {
            return true;
        }
        $redisDB = RedisCommon::getInstance()->getRedis();
        $blackKey = $this->getblackTokenKey($token);
        $cacheData = $redisDB->get($blackKey);
        if ($cacheData === "") {
            throw new FQException("user black error", 500);
        }
        if ($cacheData) {
            return true;
        }
        try {
            $this->requestApi($token);
        } catch (\Exception $e) {
            $redisDB->setex($blackKey, 120, "");
            throw new Exception($e->getMessage(), $e->getCode());
        }
        $redisDB->setex($blackKey, 120, true);
        return true;
    }

    private function requestApi($token)
    {
        //        域名
        $host = 'liteapi.fqparty.com';
        $requestUrl = sprintf("%s://%s/%s", $_SERVER['REQUEST_SCHEME'], $host, 'api/v2/init/black');
        $paramData = $this->getHeaderData($token);
        $this->WriteBeforeParam($requestUrl, $paramData);
        $requestObj = new RequestOrm();
        $reuslt = $requestObj->post($requestUrl, $paramData);
        $responseObject = json_decode($reuslt);
        if (empty($responseObject)) {
            throw new FQException("user black error", 500);
        }
//        拉黑用户
        if (!isset($responseObject->code) || $responseObject->code != 200) {
            throw new FQException($responseObject->desc);
        }
        return true;
    }

    private function getblackTokenKey($token)
    {
        if (empty($token)) {
            throw new FQException("token fatal", 500);
        }
        return sprintf("%s_blacktoken", $token);
    }

    private function getHeaderData($token)
    {
        $data['channel'] = Request::header('channel', '');       //帖子id
        $data['riq'] = Request::header('riq', 0);       //帖子id
        $data['loginIp'] = Request::header('loginIp', "");       //帖子id
        $data['deviceId'] = Request::header('deviceId', "");       //帖子id
        $data['version'] = Request::header('version', "");       //帖子id
        $data['platform'] = Request::header('platform', "");       //帖子id
        $data['postion'] = Request::header('postion', "");       //帖子id
        $data['userId'] = Request::header('userId', "");       //帖子id
        $data['token'] = $token;       //帖子id
        $data['userId'] = $this->getUserId($data['token']);
        return $data;
    }

    private function getUserId($token)
    {
        $redisinit = RedisCommon::getInstance()->getRedis();
        return intval($redisinit->get($token));
    }

    private function WriteBeforeParam($requestLink, $requestParam)
    {
        Log::info(sprintf('WriteBeforeParam--%s--link:%s--param:%s', $this->requestHash, $requestLink, json_encode($requestParam)));
    }


    private function WriteAfterResponse($content)
    {
        Log::info(sprintf('WriteAfterResponse--%s--param:%s', $this->requestHash, $content));
    }

}
