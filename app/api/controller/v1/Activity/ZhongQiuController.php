<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\zhongqiu\ZhongQiuService;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\utils\CommonUtil;

class ZhongQiuController extends BaseController
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

        $giftId = ZhongQiuService::getInstance()->giftKindId;
        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);

        $redis = RedisCommon::getInstance()->getRedis();
        $count = $redis->hGet(ZhongQiuService::getInstance()->buildKey(), $userId);
        return rjsonFit([
            'count' => intval($count),
            'giftName' => $giftKind->name,
            'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
        ]);
    }

    public function postAddress()
    {
        $userId = $this->checkMToken();
        if (ZhongQiuService::getInstance()->isExpire()){
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
        ZhongQiuService::getInstance()->postAddress($userId, $name, $mobile, $region, $address, $timestamp);

        return rjsonFit($msg='领取成功，请耐心等待哦～');
    }
}