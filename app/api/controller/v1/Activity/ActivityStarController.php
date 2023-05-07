<?php

namespace app\api\controller\v1\Activity;

use app\common\RedisCommon;
use app\domain\activity\weekStar\ZhouXingService;
use \app\facade\RequestAes as Request;

class ActivityStarController
{
    /**
     * @return mixed
     * @周星月星榜单
     * @dongbozhao
     * @最后修改时间：2021-01-20
     */
    public function GiftUserList()
    {
        $token = Request::param('mtoken');
        $redis = RedisCommon::getInstance()->getRedis();
        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }
        $uid = $redis->get($token);
        if (!$uid) {
            return rjson([], 500, '用户信息错误');
        }
        try {
            $list = ZhouXingService::getInstance()->weekStarQuery($uid);
        } catch (\Exception $e) {
            return rjson([], 500, $e->getMessage());
        }

        return rjson($list, 200, '获取成功');
    }
}