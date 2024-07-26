<?php
/*
 * 房间管理类
 */
namespace app\api\controller\v1;

use app\BaseController;
use app\common\WanMoGameCommon;
use app\facade\RequestAes as Request;
use think\facade\Log;


class CompetitionController extends BaseController
{
    public function getList()
    {
        $name = "CODM_activision";
        $list = WanMoGameCommon::getInstance()->getGameModelList($name);

        return rjson($list);
    }

    public function notifyRoom()
    {
        $params = Request::param();
        $paramsStr = json_encode($params);

        Log::info(sprintf('CompetitionController::notifyRoom params=%s', $paramsStr));

        return rjson([],0,"请求成功");
    }

    public function notifyStatus()
    {
        $params = Request::param();
        $paramsStr = json_encode($params);

        Log::info(sprintf('CompetitionController::notifyStatus params=%s', $paramsStr));

        return rjson([],0,"请求成功");
    }

    public function notifySyncUsers()
    {
        $params = Request::param();
        $paramsStr = json_encode($params);

        Log::info(sprintf('CompetitionController::notifySyncUsers params=%s', $paramsStr));

        $userList = [
            ['outUserId' => '1056141', 'roleName'=> '您'],
            ['outUserId' => '1009983', 'roleName'=> '哈哈哈'],
        ];

        $result = [
            'infos' => $userList
        ];
        return rjson($result,0,"请求成功");
    }
}