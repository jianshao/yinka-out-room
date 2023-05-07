<?php


namespace app\api\controller\v1\Activity;


use app\common\RedisCommon;
use app\domain\activity\sweet\SweetJourneyService;
use app\domain\exceptions\FQException;
use \app\facade\RequestAes as Request;

class SweetJourneyController
{
    public function SweetInfo()
    {
        $token = Request::param('mtoken');
        if (!$token) {
            return game_rjson([], 500, '用户信息错误');
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return game_rjson([], 500, '用户信息错误');
        }
        try {
            $res = SweetJourneyService::getInstance()->getPageInfo($userId);
            return game_rjson($res);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    /**
     * 宝箱领取礼物
     */
    public function getActivityBox() {
        $token = Request::param('mtoken');
        if (!$token) {
            return game_rjson([], 500, '用户信息错误');
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return game_rjson([], 500, '用户信息错误');
        }
        $receiveGiftId = Request::param('receiveGiftId');
        if (!$receiveGiftId) {
            return game_rjson('参数错误');
        }
        try {
            $rewardIndexInfo = SweetJourneyService::getInstance()->getActivityBox($userId, $receiveGiftId);
            return game_rjson($rewardIndexInfo,200,'领取成功');
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }
}