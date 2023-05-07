<?php


namespace app\utils;


use app\common\RedisCommon;
use app\domain\exceptions\FQException;

trait AdminAuthTrait
{

    /**
     * @return mixed
     * @throws FQException
     */
    protected function checkAuthInner()
    {
        $operatorId = $this->request->param('operatorId');
        $token = $this->request->param('token');
        if (CommonUtil::getAppDev()) {
            return $operatorId;
        }
        if (empty($token)) {
            throw new FQException('checkAuth fatal error token empty', 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $adminToken = $redis->get('admin_token_' . $operatorId);
        if ($token != $adminToken) {
            throw new FQException('鉴权失败', 500);
        }

        return $operatorId;
    }



    protected function checkAuthGuild()
    {
        if (empty($this->headUid)) {
            throw new FQException("用户信息错误请检查", 500);
        }
    }

}