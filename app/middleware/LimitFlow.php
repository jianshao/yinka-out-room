<?php

namespace app\middleware;

//redis lite限流 default:60s2次 600s30次 3600s200次
use app\domain\exceptions\FQException;
use app\utils\Error;
use think\Exception;

class LimitFlow
{
    public function handle($request, \Closure $next)
    {
        try {
//            if (config("config.appDev") === 'dev') {
//                return $next($request);
//            }
            //ip限流
//            $this->fitAuthIp($request);
//            //token 限流
            $this->fitAuthToken($request);
            return $next($request);
        } catch (\Exception $e) {
            return rjsonFit([], $e->getCode(), $e->getMessage());
        }
    }

    private function fitAuthIp($request)
    {
        $ip = $request->ip(0, false);
        if (empty($ip)) {
            throw  new FQException(Error::getInstance()->GetMsg(Error::ERROT), Error::ERROT);
        }
        $cacheKey = sprintf("LimitFlow-Ip:%s", $ip);
        $rules = [
            60 => 300,
            600 => 1800,
            3600 => 18000,
        ];
        $server = new \app\common\server\LimitFlow($cacheKey, $rules);
        if ($server->isPass()) {
            throw new Exception('操作频繁，请稍后再试', 500);
        }
        return true;
    }

    private function fitAuthToken($request)
    {
        $token = $request->header('token', "");
        if (empty($token)) {
            throw  new FQException(Error::getInstance()->GetMsg(Error::ERROR_TOKEN_FATAL), Error::ERROR_TOKEN_FATAL);
        }

        $controller = $request->controller(true);
        $method = $request->action(true);
        $cacheKey = sprintf("LimitFlow_c:%s_m:%s_t:%s", $controller, $method, $token);
        $rules = [
            60 => 30,
            3600 => 1800,
            86400 => 40000,
        ];
        $server = new \app\common\server\LimitFlow($cacheKey, $rules);
        if ($server->isPass()) {
            throw new Exception('操作频繁，请稍后再试', 500);
        }
        return true;
    }

}
