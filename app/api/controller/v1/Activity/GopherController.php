<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\query\bi\service\BiOrderService;
use app\domain\exceptions\FQException;
use \app\facade\RequestAes as Request;
use app\utils\TimeUtil;
use think\facade\Log;

class GopherController extends BaseController
{
    private function checkMToken() {
        $token = $this->request->param('mtoken');
        $redis = RedisCommon::getInstance()->getRedis();
        if (!$token) {
            throw new FQException('用户信息错误', 500);
        }
        $userId = $redis->get($token);
        if (!$userId) {
            throw new FQException('用户信息错误', 500);
        }
        return intval($userId);
    }

    /***
     * 活动流水
     * @return \think\response\Json
     */
    public function activityDetails()
    {
        $userId = $this->checkMToken();

        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');
        $activityType = Request::param('activityType');  //turntable gopher
        $timeStr = Request::param('timeStr', strtotime(date("Y-m-01")),'intval');
        if (!TimeUtil::isTimestamp((int)$timeStr)) {
            $timeStr = strtotime(date("Y-m-01"));
        }
        $queryStartDate = date('Y-m-01', $timeStr);   //2021-08-01
        $queryEndDate = date('Y-m-d', strtotime("$queryStartDate +1 month"));
        $queryStartTime = strtotime($queryStartDate);
        $queryEndTime = strtotime($queryEndDate);
        $tableName = BiOrderService::getInstance()->buildTableName($queryStartTime);
        try {
            list($total, $data) = BiOrderService::getInstance()->getActivityDetailList($tableName, $page, $pageNum, $userId, $activityType, $queryStartTime, $queryEndTime);
            $pageInfo = array('page' => (int)$page, 'pageNum' => (int)$pageNum, 'totalPage' => ceil($total/$pageNum));
            return rjsonFit(['list' => $data, 'pageInfo' => $pageInfo]);
        } catch (\Exception $e) {
            Log::error(sprintf('GopherController::activityDetails $userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return rjsonFit([], 500, '服务器错误');
        }
    }
}