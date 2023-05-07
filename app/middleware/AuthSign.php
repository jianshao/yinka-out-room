<?php

namespace app\middleware;

//验证签名
use app\domain\exceptions\FQException;
use app\facade\RequestAes as Request;
use app\utils\Error;
use app\utils\Support;

//api 请求的签名验证
class AuthSign
{
    private $signSalt;


    public function __construct()
    {
        $this->signSalt = config("config.apiSignSalt");
    }


    public function handle($request, \Closure $next)
    {
        $originParam = Request::param();
        $this->fitHandle($originParam);
        return $next($request);
    }

    private function fitHandle($originParam)
    {
        $enable = config("config.apiSignEnable");
        if ($enable !== "enable") {
            return;
        }
        if (empty($this->signSalt)) {
            throw new FQException("fatal error config apiSignKey error");
        }
        $support = new Support(['key' => $this->signSalt]);
        if (!isset($originParam['sign'])) {
            throw new FQException(Error::getInstance()->GetMsg(Error::API_SIGN_ERROR), Error::API_SIGN_ERROR);
        }
        if ($support->generateSign($originParam) !== strtoupper($originParam['sign'])) {
            throw new FQException(Error::getInstance()->GetMsg(Error::API_SIGN_ERROR), Error::API_SIGN_ERROR);
        }
        return;
    }

}
