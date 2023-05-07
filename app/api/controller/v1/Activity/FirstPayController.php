<?php

namespace app\api\controller\v1\Activity;

use app\common\RedisCommon;
use app\domain\pay\ChargeService;
use \app\facade\RequestAes as Request;

class FirstPayController
{
    public function firstPayInfo() {
        $res = ChargeService::getInstance()->getFirstPayInfo();
        return rjson($res);
    }
}