<?php

namespace app\web\middleware;

use app\common\RedisCommon;
use app\domain\user\dao\UserBlackModelDao;
use app\utils\Error;
use think\Exception;

class AuthToken
{
    public function handle($request, \Closure $next)
    {
        try {
            $this->_checkToken($request);
        } catch (\Exception $e) {
            return rjson([], Error::ERROR_FATAL, $e->getMessage());
        }
        return $next($request);
    }


    private function _checkToken($request)
    {
        $token = $this->getToken($request);
        if (empty($token)) {
            throw new Exception(Error::getInstance()->GetMsg(Error::ERROR_TOKEN_FATAL), Error::ERROR_TOKEN_FATAL);
        }
        $redisinit = RedisCommon::getInstance()->getRedis();
        $content = $redisinit->get($token);
        $userinfo = json_decode($content,true);
        if (empty($userinfo)) {
            throw new Exception(Error::getInstance()->GetMsg(Error::ERROR_TOKEN_FATAL), Error::ERROR_TOKEN_FATAL);
        }
        $model = UserBlackModelDao::getInstance()->loadData($userinfo['id']);
        if ($model && $model->status == 1) {
            $reason = $model->reason;
            throw new Exception('因' . $reason . ',账号封禁异常');
        }
    }

    private function getToken($request)
    {
        $token = $request->header('token');
        if (empty($token)) {//兼容老版
            $token = $request->param('token');
        }
        return $token;
    }
}
