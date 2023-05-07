<?php
/*
 * 访客类
 */

namespace app\api\controller\v1;

use app\domain\exceptions\FQException;
use app\domain\user\service\UserInfoService;
use app\domain\user\service\VisitorService;
use app\domain\vip\service\VipService;
use app\query\user\service\VisitorService as QueryVisitorService;
use app\utils\CommonUtil;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use think\facade\Log;


class VisitorController extends ApiBaseController
{
    public function viewVisitor($visitor) {
        return [
            'user_id' => $visitor->userId,
            'nickname' => $visitor->nickname,
            'avatar' => CommonUtil::buildImageUrl($visitor->avatar),
            'intro' => $visitor->intro,
            'sex' => $visitor->sex,
            'lv_dengji' => $visitor->lvDengji,
            'ctime' => formatTimes($visitor->visitorTime),
            'is_vip' => $visitor->vipLevel,
            'duke_grade' => $visitor->dukeLevel,
            'isAttention' => $visitor->isAttention,
            'visit_count' => (int)$visitor->visitorCount,
            'last_time' => $visitor->visitorTime,
        ];
    }
    /*
     * 访客列表
     * @param $token   token值
     * @param $page    分页
     */
    public function getList()
    {
        //获取数据
        $page = Request::param('page');
        if (!$page) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);
        $pageSize = 20;

        try {
            $offset = ($page - 1) * $pageSize;
            list($visitors, $count) = QueryVisitorService::getInstance()->queryVisitor($userId, $offset,$page * $pageSize-1);
            $visitorList = [];
            foreach ($visitors as $visitor) {
                $visitorList[]= $this->viewVisitor($visitor);
            }
            // 今日被访问的次数
            $time = time();
            $timeStr = date('Ymd', $time);
            $todayCount = UserInfoService::getInstance()->getNewVisitTodayUserCount($userId,$timeStr);
            // 今日访问人数
            $startTime = strtotime(date('Y-m-d', $time));
            $visitorUserCount = QueryVisitorService::getInstance()->getVisitorUserByTime($userId, $startTime, $time);
            return rjson([
                'visitor_list' => $visitorList,
                'visitor_count' => $count,    // 访问总人数
                'visitor_today_count' => $todayCount,  // 今日访问次数
                'visitor_today_user_count' => $visitorUserCount, // 今日访问人数
                'visitor_rule_dec' => '仅展示3个月内访问详情数据哦',
                'pageInfo' => [
                    'page' => $page,
                    'pageNum' => $pageSize,
                    'totalPage' => ceil($count / $pageSize)
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 设置隐身访问
     * @return \think\response\Json
     */
    public function setHiddenVisitor()
    {
        $type = intval(Request::param('type'));  // 1:隐身访问  2:取消隐身
        $toUserid = Request::param('to_userid');
        if (!$type || !$toUserid) {
            return rjson([], 500, '参数错误');
        }
        $userId = $this->headUid;
        try {
            $isOpenSvip = VipService::getInstance()->isOpenVip($userId, 2);
            if (!$isOpenSvip) {
                throw new FQException('您不是SVIP用户', 500);
            }

            VisitorService::getInstance()->setHiddenVisitor($userId, $toUserid, $type);
        } catch (\Exception $e) {
            Log::error(sprintf('VisitorController setHiddenVisitor Failed userId=%d touserId=%d type=%d errmsg=%d',
                $userId, $toUserid, $type, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

    /**
     * @desc 隐身访问列表
     * @return \think\response\Json
     */
    public function getHiddenVisitorList()
    {
        //获取数据
        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');

        $userId = $this->headUid;

        list($total, $data) = QueryVisitorService::getInstance()->getHiddenVisitorList($userId, $page, $pageNum);

        $pageInfo = array('page' => (int)$page, 'pageNum' => (int)$pageNum, 'totalPage' => ceil($total / $pageNum));
        $result = [
            'list' => $data,
            'pageInfo' => $pageInfo,
        ];
        return rjson($result);
    }
}


