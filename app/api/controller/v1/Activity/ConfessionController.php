<?php


namespace app\api\controller\v1\Activity;


use app\common\RedisCommon;
use app\domain\activity\confessionLove\ConfessionLoveService;
use app\domain\exceptions\FQException;
use \app\facade\RequestAes as Request;

class ConfessionController
{
    public function LoveWallInfo()
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
            $res = ConfessionLoveService::getInstance()->getPageInfo($userId);
            return game_rjson($res);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }

    public function loveWallData() {
        $token = Request::param('mtoken');
        $giftId = Request::param('giftId');
        if (!$token) {
            return game_rjson([], 500, '用户信息错误');
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userId = $redis->get($token);
        if (!$userId) {
            return game_rjson([], 500, '用户信息错误');
        }
        try {
            $res = ConfessionLoveService::getInstance()->getLoveWall($giftId);
            return game_rjson($res);
        } catch (FQException $e) {
            return game_rjson([], $e->getCode(),$e->getMessage());
        }
    }
}