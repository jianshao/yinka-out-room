<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\christmas\ChristmasService;
use app\domain\activity\christmas\ChristmasUserDao;
use app\domain\activity\christmas\Config;
use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\prop\PropSystem;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class ChristmasController extends BaseController
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

        $config = Config::loadConf();
        $giftList = [];
        $giftIds = ArrayUtil::safeGet($config, 'giftIds', []);
        foreach ($giftIds as $giftId){
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if (!empty($giftKind)){
                $giftList[] = [
                    'name' => $giftKind->name,
                    'price' => $giftKind->price ? $giftKind->price->count : 0,
                    'image' => CommonUtil::buildImageUrl($giftKind->image),
                ];
            }
        }

        usort($giftList, function($a, $b) {
            if ($a['price'] < $b['price']) {
                return -1;
            } else if ($a['price'] > $b['price']) {
                return 1;
            }
            return 0;
        });

        $propList = [];
        $exchangeConf = ArrayUtil::safeGet($config, 'exchangeConf', []);
        foreach ($exchangeConf as $propId => $lingdang){
            $propKind = PropSystem::getInstance()->findPropKind($propId);
            if (!empty($propKind)){
                $propList[] = [
                    'propId' => $propKind->kindId,
                    'name' => $propKind->name,
                    'image' => CommonUtil::buildImageUrl($propKind->image),
                    'count' => 1,
                    'lingdang' => $lingdang
                ];
            }
        }

        usort($propList, function($a, $b) {
            if ($a['lingdang'] < $b['lingdang']) {
                return 1;
            } else if ($a['lingdang'] > $b['lingdang']) {
                return -1;
            }
            return 0;
        });

        if (ChristmasService::getInstance()->isExpire()){
            $lingdangCount = 0;
        }else{
            $lingdangCount = ChristmasUserDao::getInstance()->getLindDang($userId);
        }

        return rjsonFit([
            'lingdangCount' => $lingdangCount,
            'giftList' => $giftList,
            'propList' => $propList,
        ]);
    }

    public function doExchange()
    {
        $userId = $this->checkMToken();

        if (ChristmasService::getInstance()->isExpire()){
            throw new FQException("不在活动时间内",500);
        }

        $propId = $this->request->param('propId');

        $timestamp = time();
        $lingdangCount = ChristmasService::getInstance()->doExchange($userId, $propId, $timestamp);
        $propKind = PropSystem::getInstance()->findPropKind($propId);
        return rjsonFit([
            'lingdangCount' => $lingdangCount,
            'propId' => $propId,
            'propName' => $propKind->name,
            'propImage' => CommonUtil::buildImageUrl($propKind->image),
            'count' => 1
        ]);
    }
}