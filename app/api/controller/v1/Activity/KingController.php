<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\king\Config;
use app\domain\activity\king\KingService;
use app\domain\activity\king\KingUserDao;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;

class KingController extends BaseController
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

        $kingUser = KingUserDao::getInstance()->loadUser($userId);

        $config = Config::loadConf();
        #福袋
        $giftList = [];
        $giftKind = GiftSystem::getInstance()->findGiftKind($config['welfare1_reward']['giftId']);
        if (!empty($giftKind)){
            $giftList[] = [
                'giftName' => $giftKind->name,
                'giftCoin' => $giftKind->price ? $giftKind->price->count : 0,
                'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                'count' => $config['welfare1_reward']['count']
            ];
        }

        $giftKind = GiftSystem::getInstance()->findGiftKind($config['welfare2_reward']['giftId']);
        if (!empty($giftKind)){
            $giftList[] = [
                'giftName' => $giftKind->name,
                'giftCoin' => $giftKind->price ? $giftKind->price->count : 0,
                'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                'count' => $config['welfare1_reward']['count']
            ];
        }

        $userModel = UserModelCache::getInstance()->getUserInfo($userId);

        return rjsonFit([
            'isKing' => $userModel->dukeLevel >= 5,
            'giftList' => $giftList,
            'welfare1Status' => $kingUser->welfare1Status,
            'welfare2Status' => $kingUser->welfare2Status,
        ]);
    }

    public function getReward()
    {
        $userId = $this->checkMToken();

        if (KingService::getInstance()->isExpire()){
            throw new FQException("活动已过期",500);
        }

        $timestamp = time();
        KingService::getInstance()->getReward($userId, $timestamp);

        return rjsonFit($msg='领取成功');
    }

    public function postAddress()
    {
        $userId = $this->checkMToken();

        if (KingService::getInstance()->isExpire()){
            throw new FQException("活动已过期",500);
        }

        $name = $this->request->param('name');
        $mobile = $this->request->param('mobile');
        $region = $this->request->param('region');
        $address = $this->request->param('address');

        if (empty($name) || empty($mobile) || empty($region) || empty($address)){
            throw new FQException("参数不能为空",500);
        }

        $timestamp = time();
        KingService::getInstance()->postAddress($userId, $name, $mobile, $region, $address, $timestamp);

        return rjsonFit($msg='领取成功，请耐心等待哦～');
    }
}