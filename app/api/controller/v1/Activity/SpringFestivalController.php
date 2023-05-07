<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\springFestival\Config;
use app\domain\activity\springFestival\SpringFestivalService;
use app\domain\exceptions\FQException;

class SpringFestivalController extends BaseController
{
    /**
     * @throws \app\domain\exceptions\FQException
     */
    private function checkMToken()
    {
        $token = $this->request->param('mtoken');
        $redis = RedisCommon::getInstance()->getRedis();
        if (!$token) {
            throw new FQException('用户信息错误', 500);
        }
        $userId = $redis->get($token);
        if (!$userId) {
            throw new FQException('用户信息错误', 500);
        }
        return $userId;
    }

    /**
     * Notes: 活动首页
     * @throws \app\domain\exceptions\FQException
     */
    public function index(): \think\response\Json
    {
        $userId = $this->checkMToken();
        $redis = RedisCommon::getInstance()->getRedis();
        $res = [];
        //活动时间
        $config = Config::loadConf();
        $res['activityConfig'] = ['startTime' => date("Y-m-d", strtotime($config['startTime'])), 'stopTime' => date("Y-m-d", strtotime($config['stopTime']))];
        //福气池
        $res['poolValue'] = SpringFestivalService::getInstance()->getPoolValue();
        $res['goldBarCount'] = SpringFestivalService::getInstance()->getGoldBarCount($userId, $config);
        $res['userBankCoupletInfo'] = SpringFestivalService::getInstance()->getUserBankInfo($userId, $config, 'coupletArea');
        $res['activityConfig']['exchangeRules'] = SpringFestivalService::getInstance()->buildExchangeRules($config['exchangeRules'], $res['userBankCoupletInfo']);
        $res['userBankBangerInfo'] = SpringFestivalService::getInstance()->getUserBankInfo($userId, $config, 'bangerArea');
        return rjson($res, 200, '返回成功');
    }

    public function blessingPool(): \think\response\Json
    {
        $poolValue = SpringFestivalService::getInstance()->getPoolValue();
        return rjson(['poolValue' => $poolValue], 200, '返回成功');
    }

    /**
     * Notes: 福字兑换 （包括金条，爆竹，头像框，座驾）
     */
    public function exchange()
    {
        $userId = $this->checkMToken();
        $exchangeId = $this->request->param('exchangeId');
        $consumeId = $this->request->param('consumeId', 0);
        $config = Config::loadConf();
        if (SpringFestivalService::getInstance()->isRunning($userId, time())) {
            return SpringFestivalService::getInstance()->exchange($userId, $exchangeId, $consumeId, $config);
        } else {
            return rjson([], 500, '活动暂未开始或已经结束');
        }
    }


}