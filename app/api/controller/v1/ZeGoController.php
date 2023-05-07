<?php
namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use think\facade\Log;
use app\common\ZeGoCommon;


class ZeGoController extends ApiBaseController
{
    /**
     * 获取即构token
     * @return \think\response\Json
     */
    public function getZeGoToken()
    {
        $token = ZeGoCommon::getInstance()->getToken($this->headUid);
        return rjson([
            'token' => $token,
        ]);
    }

}