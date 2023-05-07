<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\giftReturn\Config;
use app\domain\activity\giftReturn\GiftReturnService;
use app\domain\activity\giftReturn\GiftReturnUserDao;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\gift\GiftUtils;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class GiftReturnController extends BaseController
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
        return $userId;
    }

    /**
     * 初始化
     * @return [type] [description]
     */
    public function init()
    {
        $userId = $this->checkMToken();

        $timestamp = time();
        $giftUser = GiftReturnUserDao::getInstance()->loadUser($userId, $timestamp);

        $config = Config::loadConf();
        $todayReward = intval($giftUser->beanCount*$config['rate']);
        $tomorrowReward = intval(GiftUtils::calcTotalValue($giftUser->todayGiftMap)*$config['rate']);
        #福袋
        $giftList = [];
        foreach (ArrayUtil::safeGet($config, 'giftIds', []) as $giftId){
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if (empty($giftKind))continue;
            $giftList[] = [
                'giftId' => $giftKind->kindId,
                'giftName' => $giftKind->name,
                'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                'count' => ArrayUtil::safeGet($giftUser->todayGiftMap, $giftId, 0)
            ];
        }

        return rjsonFit([
            'startTime' => $config['startTime'],
            'stopTime' => $config['stopTime'],
            'giftList' => $giftList,
            'todayReward' => $todayReward,
            'tomorrowReward' => $tomorrowReward,
            'getStatus' => $giftUser->gotReward,
        ]);
    }

    public function getReward()
    {
        $userId = $this->checkMToken();

//        if (GiftReturnService::getInstance()->isExpire()){
//            throw new FQException("活动已过期",500);
//        }

        $timestamp = time();
        $rewardCount = GiftReturnService::getInstance()->getReward($userId, $timestamp);

        return rjsonFit([
            'rewardCount' => $rewardCount,
        ]);
    }
}