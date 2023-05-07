<?php
/**
 * 首冲
 * yond
 * 
 */

namespace app\api\controller\v1;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\asset\AssetSystem;
use app\domain\exceptions\FQException;
use app\domain\pay\ChargeService;
use app\domain\pay\FirstChargeService;
use app\domain\pay\ProductAreaNames;
use app\domain\pay\ProductShelvesNames;
use app\domain\pay\ProductSystem;
use app\facade\RequestAes as Request;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use constant\FirstChargeConstant;
use think\facade\Log;

class FirstPayController extends BaseController
{
    /**
     * @desc 获取mtoken
     * @return mixed|string
     * @throws FQException
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

    //检测首冲 废弃接口不用
    public function checkFirstPay()
    {
        return rjson([]);
    }

    //检测首冲 废弃接口不用
    public function checkFirstPayPop() {
        return rjson([]);
    }

    /**
     * 安卓app内是否弹首充
     */
    public function firstChargePop()
    {
        $userId = intval($this->headUid);
        if (ChargeService::getInstance()->isFirstCharged($userId)){
            return rjsonFit(['isPop' => false]);
        }

        $timestamp = time();
        $redis = RedisCommon::getInstance()->getRedis();
        $changeTime = $redis->HGET('first_charge_pop_status'.$userId, 'changeTime');
        $need = $changeTime == 0 ? true:!TimeUtil::isSameDay($timestamp, $changeTime);
        if($need){
            $redis->HSET('first_charge_pop_status'.$userId, 'changeTime', $timestamp);
        }
        return rjsonFit([
            'isPop' => true,
            'firstUrl' => config('config.firstPayUrlIndex').$this->headToken
            ]);
    }

    /**
     * 安卓app内首充接口
     */
    public function firstChargeData()
    {
        $ret = [];
        $area = ProductSystem::getInstance()->getArea('android');
        if ($area != null) {
            $shelves = $area->findShelves('firstPay');
            if ($shelves != null && count($shelves->products) > 0) {
                $product = $shelves->products[0];

                $rewards = [];
                foreach ($product->deliveryAssets as $key => $assetItem){
                    $assetKind = AssetSystem::getInstance()->findAssetKind($assetItem->assetId);
                    $rewards[] = [
                        'name' => $assetKind->displayName,
                        'image' => CommonUtil::buildImageUrl($assetKind->image),
                        'count' => $assetItem->count,
                        'unit' => $assetKind->unit
                    ];
                }
                $ret = [
                    'id' => $product->productId,
                    'rmb' => $product->price,
                    'bean' => $product->bean,
                    'present' => $product->present,
                    'rewards' => $rewards
                ];
            }
        }

        $payChannels = ChargeService::getInstance()->getPayChannels();
        $payChannelList = [];
        foreach ($payChannels as $payChannel) {
            $payChannelList[] = [
                'id' => $payChannel->id,
                'pid' => $payChannel->pid,
                'content' => $payChannel->content,
                'check' => $payChannel->check,
                'type' => $payChannel->type
            ];
        }
        return rjsonFit([
            'charge_product' => $ret,
            'pay_channel' => $payChannelList
        ]);
    }

    /**
     * @desc 获取首充奖励详情 h5调用
     * @return \think\response\Json
     */
    public function getFirstChargeReward()
    {
        $userId = $this->checkMToken();
        // 是否首充
        $order = FirstChargeService::getInstance()->getFirstChargeOrder($userId);
        $isFirstCharge = false;
        if (ArrayUtil::safeGet($order, 'is_first_charge')) {
            $isFirstCharge = true;
        }
        // 用户领取奖品记录
        $firstChargeRewards = FirstChargeService::getInstance()->getUserRewards($userId, $isFirstCharge);
        // 动态返回 商品的id
        $iosShelves = ProductSystem::getInstance()->getProductMap(ProductAreaNames::$IOS, ProductShelvesNames::$FIRST_PAY);
        $iosProducts = array_keys($iosShelves);
        $androidShelves = ProductSystem::getInstance()->getProductMap(ProductAreaNames::$ANDROID, ProductShelvesNames::$FIRST_PAY);
        $androidSProducts = array_keys($androidShelves);
        return rjson([
            'first_charge_rewards' => $firstChargeRewards,
            'is_first_charge' => $isFirstCharge,
            'products' => [
                'ios' => $iosProducts[0] ?? 104,
                'android' => $androidSProducts[0] ?? 209,
            ]
        ]);
    }

    /**
     * @desc 首充弹框 客户端调用
     * @return \think\response\Json
     */
    public function getFirstChargePop()
    {
        $userId = intval($this->headUid);
        $popInfo = FirstChargeService::getInstance()->firstChargePop($userId);
        $popInfo['first_url'] = config('config.firstChargeUrlIndex') . "?mtoken=" . $this->headToken;

        return rjson($popInfo);
    }

    /**
     * @desc 领取首充奖励 h5调用
     */
    public function receiveFirstChargeReward()
    {
        $userId = $this->checkMToken();
        $currentDay = Request::param('current_day', 0);
        if (!$currentDay) {
            return rjson([], 500, '参数错误');
        }
        // 查询首充的 商品id
        $order = FirstChargeService::getInstance()->getFirstChargeOrderDao($userId);
        if (empty($order) || !$order->paidTime) {
            return rjson([], 500, '没有充值记录');
        }
        // 超过5个自然日
        $timestamp = time();
        $diffDays = TimeUtil::calcDiffDays($order->paidTime, $timestamp);
        if ($diffDays >= FirstChargeConstant::RECEIVE_TOTAL_DAT) {
            return rjson([], 500, '已超过5个日然日');
        }
        // 用户领取奖品记录
        $userRewards = FirstChargeService::getInstance()->getRewardsByUserId($userId);
        $receiveReward = collect($userRewards)->where('current_day', $currentDay)->first();
        if ($receiveReward) {
            return rjson([], 500, '当天已经领取');
        }
        // 不能隔天领取
        if ($currentDay > 1) {
            $receiveBeforeReward = collect($userRewards)->where('current_day', $currentDay - 1)->first();
            if (!$receiveBeforeReward) {
                return rjson([], 500, '不能隔天领取');
            }
        }
        try {
            FirstChargeService::getInstance()->receiveReward($userId, $currentDay, $order, $timestamp);
            // 领取完成全部天数，即首充活动结束
            if ($currentDay == FirstChargeConstant::FIRST_CHARGE_RECEIVE_DAY) {
                FirstChargeService::getInstance()->firstChargeFinish($userId);
            }
        } catch (\Exception $e) {
            Log::error(sprintf('FirstPayController receiveFirstChargeReward Failed userId=%d $userId=%d errmsg=%d',
                $userId, $currentDay, $e->getTraceAsString()));
            return rjson([], 500, '服务端错误');
        }

        return rjson();
    }

}