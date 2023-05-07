<?php

namespace app\api\controller\v1;

use app\domain\exceptions\FQException;
use app\utils\CommonUtil;
use think\facade\Log;
use \app\facade\RequestAes as Request;
use app\BaseController;

class ShuMeiNotifyController extends BaseController
{

    public function audioStreamNotify(){

        $data = Request::param();
        Log::record('audioStreamNotifyInfo----'.json_encode($data));

    }


}