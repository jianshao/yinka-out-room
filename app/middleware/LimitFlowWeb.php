<?php

namespace app\middleware;

//redis lite限流
use app\domain\exceptions\FQException;
use app\utils\Error;
use think\Exception;

class LimitFlowWeb
{
    public function handle($request, \Closure $next)
    {
        try {
            //ip限流
            $this->fitAuthIp($request);
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
            60 => 200,
            600 => 2000,
            3600 => 12000,
        ];
        $server = new \app\common\server\LimitFlow($cacheKey, $rules);
        if ($server->isPass()) {
            throw new Exception('操作频繁，请稍后再试', 500);
        }
        return true;
    }

    /**
     * @param $request
     * @return bool
     * @throws Exception
     */
    private function fitAuthToken($request)
    {
        $token = $request->param('mtoken', "");
        if (empty($token)) {
            return true;
        }
        $controller = $request->controller(true);
        $method = $request->action(true);
        $cacheKey = sprintf("LimitFlow_c:%s_m:%s_t:%s", $controller, $method, $token);
        $rules = [
            60 => 20,
            3600 => 1200,
            86400 => 28800,
        ];
        $server = new \app\common\server\LimitFlow($cacheKey, $rules);
        if ($server->isPass()) {
            throw new Exception('操作频繁，请稍后再试', 500);
        }
        return true;
    }

}
