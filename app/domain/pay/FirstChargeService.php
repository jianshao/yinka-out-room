<?php


namespace app\domain\pay;

use app\common\RedisCommon;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\Config;
use app\domain\exceptions\FQException;
use app\domain\pay\dao\FirstChargeRewardModelDao;
use app\domain\pay\dao\OrderModelDao;
use app\domain\pay\model\FirstChargeRewardModel;
use app\domain\queue\producer\YunXinMsg;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use constant\FirstChargeConstant;
use think\facade\Log;
use Exception;

class FirstChargeService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FirstChargeService();
        }
        return self::$instance;
    }


    /**
     * @desc 获取首充奖励
     */
    public function getFirstChargeReward()
    {
        $fistChargeReward = Config::getInstance()->getFistChargeRewardConf();
        return ArrayUtil::safeGet($fistChargeReward, 'rewards');
    }

    /**
     * @desc 获取用户奖励
     * @param $userId
     * @return array|mixed
     */
    public function getUserRewards($userId, $isFirstCharge)
    {
        $userRewards = [];
        if ($isFirstCharge){
            $userRewards = $this->getRewardsByUserId($userId);
        }
        // 领取状态  可领取、待领取、已领取
        $firstChargeRewards = $this->getFirstChargeReward();
        $lastReceiveReward = [];
        foreach ($firstChargeRewards as $key => $reward) {
            // 图片添加域名
            foreach (ArrayUtil::safeGet($reward, 'rewards') as $k => $item) {
                $firstChargeRewards[$key]['rewards'][$k]['img'] = CommonUtil::buildImageUrl(ArrayUtil::safeGet($item, 'img'));
            }
            if (!empty($userRewards)) {
                foreach ($userRewards as $userReward) {
                    // 用户是否领取奖励
                    if (ArrayUtil::safeGet($reward, 'current_day')
                        == ArrayUtil::safeGet($userReward, 'current_day')
                    ) {
                        $firstChargeRewards[$key]['receive_status'] = FirstChargeConstant::ALREADY_RECEIVE_STATUS;
                        $lastReceiveReward = $userReward;
                    }
                }
            }
        }
        $lastReceiveDay = ArrayUtil::safeGet($lastReceiveReward, 'current_day') ?? 0;
        if ($lastReceiveDay != FirstChargeConstant::ALREADY_RECEIVE_STATUS) {
            foreach ($firstChargeRewards as $key => $reward) {
                if ($key >= $lastReceiveDay) {
                    $firstChargeRewards[$key]['receive_status'] = FirstChargeConstant::WAIT_RECEIVE_STATUS;
                }
                // 判断当前是否可以领取
                if (TimeUtil::calcDayStartTimestamp(ArrayUtil::safeGet($lastReceiveReward, 'created_time'))
                    != TimeUtil::calcDayStartTimestamp(time())
                ) {
                    $firstChargeRewards[$lastReceiveDay]['receive_status'] = FirstChargeConstant::CAN_RECEIVE_STATUS;
                }
            }
        }
        return $firstChargeRewards;
    }

    /**
     * @desc 获取首充状态
     * @param $userId
     * @return array
     */
    public function firstChargePop($userId)
    {
        // $chargeStatus  0表示未充值   1表示已充值   2表示首充充值
        $chargeStatus = FirstChargeConstant::NOT_CHARGE_STATUS;
        $isPopIcon = false;
        $isReceiveToday = false;
        // 是否充值
        $redis = RedisCommon::getInstance()->getRedis();
        $isChargedBeen = $redis->sIsMember(FirstChargeConstant::USER_RECHARGED_BEEN, $userId);
        $timestamp = time();
        if ($isChargedBeen) {
            $chargeStatus = FirstChargeConstant::ALREADY_CHARGE_STATUS;
            $order = $this->getFirstChargeOrder($userId);
            $firstChargeSucTime = ArrayUtil::safeGet($order,'first_charge_suc_time');
            // 判断是否首充
            if (ArrayUtil::safeGet($order,'is_first_charge') && ArrayUtil::safeGet($order,'first_charge_suc_time')) {
                $chargeStatus = FirstChargeConstant::FIRST_CHARGE_STATUS;
                $diffDays = TimeUtil::calcDiffDays($firstChargeSucTime, $timestamp);
                // 首充成功之后5个自然日内可领取奖励
                if ($diffDays < FirstChargeConstant::RECEIVE_TOTAL_DAT) {
                    $userRewards = $this->getRewardsByUserId($userId);
                    // 5天内未领取完成显示 icon
                    if (count($userRewards) != FirstChargeConstant::FIRST_CHARGE_RECEIVE_DAY) {
                        $isPopIcon = true;
                    }
                    // 当天是否领取成功
                    $lastReceiveReward = collect($userRewards)->pop();
                    if ($lastReceiveReward) {
                        if (TimeUtil::calcDayStartTimestamp(ArrayUtil::safeGet($lastReceiveReward, 'created_time'))
                            == TimeUtil::calcDayStartTimestamp($timestamp)
                        ) {
                            $isReceiveToday = true;
                        }
                    }
                } else {
                    // 首充订单超过5天 首充结束
                    $this->firstChargeFinish($userId);
                }
            } else {
                // 充值过、没有首充订单 首充结束
                $this->firstChargeFinish($userId);
            }
        }

        return [
            'charge_status' => $chargeStatus,
            'is_receive_today' => $isReceiveToday,
            'is_pop_icon' => $isPopIcon,
            'down_time' => FirstChargeConstant::DOWN_TIME_TOTAL_ICON
        ];
    }

    /**
     * @desc 领取奖励
     * @param $userId
     * @param $currentDay
     * @param $order
     * @param $timestamp
     * @return bool
     * @throws FQException
     */
    public function receiveReward($userId, $currentDay, $order, $timestamp)
    {
        // 首充奖励
        $firstChargeRewards = $this->getFirstChargeReward();
        $firstChargeRewards = collect($firstChargeRewards)->where('current_day', $currentDay)->first();
        $biEvent = BIReport::getInstance()->makeFirstChargeRewardBIEvent($order->orderId, $order->productId, $order->payChannel,true);
        try {
            // 添加奖励领取记录
            $this->insertFirstChargeReward($userId, $currentDay, $timestamp, ArrayUtil::safeGet($firstChargeRewards, 'rewards', []));
            // 添加资产
            if (ArrayUtil::safeGet($firstChargeRewards, 'rewards')) {
                $assetList = [];
                $msg = '恭喜您获得首充奖励：';
                foreach ($firstChargeRewards['rewards'] as $rewards) {
                    $assetList[] = [
                        ArrayUtil::safeGet($rewards, 'assetId'),
                        ArrayUtil::safeGet($rewards, 'count'),
                        $biEvent
                    ];
                    $msg .= sprintf("%s*%s、", ArrayUtil::safeGet($rewards, 'name'), ArrayUtil::safeGet($rewards, 'count'));
                }
                AssetUtils::addAssets($userId, $assetList, $timestamp);
                $msg = rtrim($msg, '、');
                $msg .= '。头像框需手动佩戴；礼物自动进入背包库存；金币账户自动添加获得金币的数量。';
                // 发送小秘书消息
                $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => $msg]);
                Log::info(sprintf('receiveReward TakeShot toUid=%d, resMsg=%s', $userId, $resMsg));
            }
        } catch (Exception $e) {
            Log::error(sprintf('FirstPayController receiveReward Failed userId=%d $userId=%d errmsg=%d',
                $userId, $currentDay, $e->getTraceAsString()));
            throw $e;
        }
        return true;
    }

    /**
     * @desc 首充活动结束
     * @param $userId
     */
    public function firstChargeFinish($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->hset(sprintf('userinfo_%s', $userId), FirstChargeConstant::FIRST_CHARGE_FINISH, true);
        return true;
    }

    /**
     * @desc 设置首充
     * @param $userId
     * @return bool
     */
    public function setFirstPayNotice($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->hset(sprintf('userinfo_%s', $userId), 'first_pay_notice', 1);
        $redis->sAdd(FirstChargeConstant::USER_RECHARGED_BEEN, $userId);
        return true;
    }

    /**
     * @desc 添加奖励领取记录
     * @param $userId
     * @param $currentDay
     * @param int $timestamp
     * @param array $rewards
     * @return FirstChargeRewardModel
     */
    public function insertFirstChargeReward($userId, $currentDay, int $timestamp, array $rewards = [])
    {
        $firstChargeReward = new FirstChargeRewardModel();
        $firstChargeReward->userId = $userId;
        $firstChargeReward->currentDay = $currentDay;
        $firstChargeReward->rewards = json_encode($rewards);
        $firstChargeReward->createdTime = $timestamp;
        FirstChargeRewardModelDao::getInstance()->storeModel($firstChargeReward);
        return $firstChargeReward;
    }

    /**
     * @desc 获取用户的奖励领取记录
     * @param int $userId
     */
    public function getRewardsByUserId(int $userId)
    {
        return FirstChargeRewardModelDao::getInstance()->getRewardsByUserId($userId);
    }

    /**
     * @desc 获取首充订单
     * @param $userId
     */
    public function getFirstChargeOrder($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $isFirstCharge = $redis->hget(sprintf('userinfo_%s', $userId), FirstChargeConstant::FIRST_CHARGE_REDIS_KEY);
        $successTime = $redis->hget(sprintf('userinfo_%s', $userId), FirstChargeConstant::FIRST_CHARGE_SUC_TIME) ?? 0;
        $firstChargeFinish = $redis->hget(sprintf('userinfo_%s', $userId), FirstChargeConstant::FIRST_CHARGE_FINISH);
        return [
            'is_first_charge' => $isFirstCharge,
            'first_charge_suc_time' => $successTime,
            'first_charge_finish' => $firstChargeFinish,
        ];
    }

    /**
     * @desc 获取首充详细订单
     * @param $userId
     * @return model\Order|null
     */
    public function getFirstChargeOrderDao($userId)
    {
        $firstChargeProducts = $this->getFirstChargeProduct();
        $chargeWhere = [];
        $chargeWhere[] = ['uid', '=', $userId];
        $chargeWhere[] = ['product_id', 'in', $firstChargeProducts];
        $chargeWhere[] = ['status', '=', OrderStates::$DELIVERY];
        return OrderModelDao::getInstance()->getInfo($chargeWhere);
    }

    /**
     * @desc 获取首充商品id
     */
    public function getFirstChargeProduct()
    {
        $iosShelves = ProductSystem::getInstance()->getProductMap(ProductAreaNames::$IOS, ProductShelvesNames::$FIRST_PAY);
        $iosProducts = array_keys($iosShelves);
        $androidShelves = ProductSystem::getInstance()->getProductMap(ProductAreaNames::$ANDROID, ProductShelvesNames::$FIRST_PAY);
        $androidSProducts = array_keys($androidShelves);
        return array_merge($iosProducts, $androidSProducts);
    }

    /**
     * @desc 首充信息添加缓存
     * @param $userId
     * @param $productId
     * @param $timestamp
     * @param bool
     */
    public function cacheFirstChargeInfo($userId, $productId, $timestamp)
    {
        $firstChargeProducts = $this->getFirstChargeProduct();
        if (in_array($productId, $firstChargeProducts)) {
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->hset(sprintf('userinfo_%s', $userId), FirstChargeConstant::FIRST_CHARGE_REDIS_KEY, true);
            $redis->hset(sprintf('userinfo_%s', $userId), FirstChargeConstant::FIRST_CHARGE_SUC_TIME, $timestamp);
        }
        return true;
    }

}