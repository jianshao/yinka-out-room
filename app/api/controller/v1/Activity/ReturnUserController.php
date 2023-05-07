<?php

namespace app\api\controller\v1\Activity;

use app\common\RedisCommon;
use app\domain\activity\recallUser\RecallUserService;
use app\domain\exceptions\FQException;
use \app\facade\RequestAes as Request;

class ReturnUserController
{
    protected $return_user_activity_key = 'return_user_activity_config';
    /**
     *  开年回归活动详情
     */
    public function returnActivityInfo() {
        $token = Request::param('mtoken');
        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return rjson([], 500, '用户信息错误');
        }
        $time = time();
        $activityConfig = $redis->hGetAll($this->return_user_activity_key);
        if ($time < $activityConfig['start_time']) {
            return rjson(['returnStarStatus' => 0, 'returnGiftStatus' => 0, 'returnChargeStatus' => 0],200,'活动未开始');
        }
        if ($time >= $activityConfig['end_time']) {
            return rjson(['returnStarStatus' => 1, 'returnGiftStatus' => 1, 'returnChargeStatus' => 1],200,'活动已结束');
        }
        $returnStarStatus = $redis->hget(sprintf('return_star_%s', $activityConfig['id']), $userId);
        $res['returnStarStatus'] = $returnStarStatus ? (int)$returnStarStatus : 0;
        $returnGiftStatus = $redis->hget(sprintf('return_gift_%s', $activityConfig['id']), $userId);
        $res['returnGiftStatus'] = $returnGiftStatus ? (int)$returnGiftStatus : 0;
        $returnChargeStatus = $redis->hget(sprintf('return_charge_%s', $activityConfig['id']), $userId);
        $res['returnChargeStatus'] = $returnChargeStatus ? (int)$returnChargeStatus : 0;
        return game_rjson($res);
    }

    /**
     * 领取回归之星
     */
    public function receiveReturnStar() {
        $token = Request::param('mtoken');
        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return rjson([], 500, '用户信息错误');
        }
        try {
            RecallUserService::getInstance()->receiveReturnStar($userId, $redis);
            return rjson(['returnStarStatus' => 1],200,'领取成功');
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(), $e->getMessage());
        }


    }

    /**
     * 领取回归礼物
     */
    public function receiveReturnGift() {
        $token = Request::param('mtoken');
        $type = Request::param('type'); //左边或者右边
        if(!$type) {
            return rjson([],500,'参数错误');
        }
        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return rjson([], 500, '用户信息错误');
        }

        try {
            RecallUserService::getInstance()->receiveReturnGift($userId, $redis, $type);
            return rjson(['returnGiftStatus' => 1],200,'领取成功');
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 领取回归充值礼物
     */
    public function receiveReturnCharge() {
        $token = Request::param('mtoken');
        if (!$token) {
            return rjson([], 500, '用户信息错误');
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return rjson([], 500, '用户信息错误');
        }
        try {
            RecallUserService::getInstance()->receiveReturnCharge($userId, $redis);
            return rjson(['returnChargeStatus' => 1],200,'领取成功');
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(), $e->getMessage());
        }
    }
}