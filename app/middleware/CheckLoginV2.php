<?php

namespace app\middleware;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\utils\Error;
use app\utils\HelperUnit;


/**
 * @info 验证登陆状态 黑名单过滤
 * Class CheckLoginV2
 * @package app\middleware
 */
class CheckLoginV2
{
    public function handle($request, \Closure $next)
    {
        $this->fithandle();
        return $next($request);
    }

    private function fithandle()
    {
        $token = HelperUnit::getToken();
        if (empty($token)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_TOKEN_FATAL), Error::ERROR_TOKEN_FATAL);
        }
        $redisinit = RedisCommon::getInstance()->getRedis();
        $uid = $redisinit->get($token);
        if (empty($uid)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::ERROR_TOKEN_FATAL), Error::ERROR_TOKEN_FATAL);
        }
//        $model = UserBlackModelDao::getInstance()->loadData($uid);
//        if ($model && $model->status == 1) {
//            $reason = $model->reason;
//            throw new FQException('因' . $reason . ',账号封禁异常');
//        }
    }

}
