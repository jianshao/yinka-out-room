<?php

namespace app\domain\user\service;

use app\common\RedisLock;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\bi\BIConfig;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\CoinDao;
use app\query\user\dao\DaiChongModelDao;
use app\domain\user\dao\DiamondModelDao;
use app\domain\user\dao\MemberDetailModelDao;
use app\domain\user\dao\TodayEarningsModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\event\BeanExchangeCoinDomainEvent;
use app\domain\user\UserRepository;
use app\domain\user\event\DiamondExchangeBeanDomainEvent;
use app\event\TradeUnionAgentEvent;
use app\utils\CommonUtil;
use think\facade\Log;
use Exception;

class WalletService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new WalletService();
        }
        return self::$instance;
    }

    /**
     * 钻石兑换豆
     *
     * @param $diamondCount
     */
    public function diamondExchangeBean($userId, $diamondCount)
    {
        if (!is_integer($diamondCount)) {
            throw new FQException('兑换参数错误', 500);
        }

        if ($diamondCount < 10000) {
            throw new FQException('兑换数量错误', 500);
        }

        if ($diamondCount % 10000 != 0) {
            throw new FQException('兑换数量错误', 500);
        }

        $timestamp = time();

        list($bean, $diamond) = $this->diamondExchangeBeanImpl($userId, $diamondCount, $timestamp);

        Log::info(sprintf('DiamondExchangeBean ok userId=%d count=%d beanBalance=%d diamondBalance=%d',
            $userId, $diamondCount, $bean->balance($timestamp), $diamond->balance($timestamp)));

        return [
            $bean->balance($timestamp),
            $diamond->balance($timestamp),
        ];
    }

    /**
     * 钻石兑换豆
     *
     * @param $diamondCount
     */
    public function beanExchangeCoin($userId, $beanCount)
    {
        if (!is_integer($beanCount)) {
            throw new FQException('兑换参数错误', 500);
        }

        if ($beanCount < 1) {
            throw new FQException('兑换数量错误', 500);
        }
        $timestamp = time();

        list($beanBalance, $coinBalance) = $this->beanExchangeCoinImpl($userId, $beanCount, $timestamp);
        Log::info(sprintf('beanExchangeCoin ok userId=%d count=%d beanBalance=%d diamondBalance=%d',
            $userId, $beanCount, $beanBalance, $coinBalance));
        return [
            $beanBalance,
            $coinBalance,
        ];
    }

    private function beanExchangeCoinImpl($userId, $beanCount, $timestamp)
    {
        try {
            list($beanBalance,$coinBalance,$coinCount,$user) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $beanCount, $timestamp) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('参数错误', 5000);
                }

                if (empty($user->getUserModel()->username)) {
                    throw new FQException('您还没有绑定手机号', 5100);
                }

                // 获取用户资产
                $userAsset = $user->getAssets();
                // $bean = $userAsset->getBean($timestamp);
                $coinCount = intval($beanCount * config('config.bean_coin_scale'));
                $biEvent = BIReport::getInstance()->makeBeanExchangeCoinBIEvent($beanCount, $coinCount);
                // 减豆
                $beanBalance = $userAsset->consume(AssetKindIds::$BEAN,$beanCount, $timestamp, $biEvent);
                $coin = $userAsset->getCoin($timestamp);
                // 加金币
                $coinBalance = $coin->add($coinCount, $timestamp, $biEvent);
                return [$beanBalance, $coinBalance, $coinCount, $user];
            });
            event(new BeanExchangeCoinDomainEvent($user, $beanBalance, $beanCount, $coinBalance, $coinCount, $timestamp));
            return [$beanBalance, $coinBalance];
        } catch (Exception $e) {
            Log::error(sprintf('WalletService::beanExchangeCoinImpl userId=%d beanCount=%d ex=%d:%s',
                $userId, $beanCount, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    private function diamondExchangeBeanImpl($userId, $diamondCount, $timestamp)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $diamondCount, $timestamp) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('参数错误', 5000);
                }

                if (empty($user->getUserModel()->username)) {
                    throw new FQException('您还没有绑定手机号', 5100);
                }

                if ((int)$user->getUserModel()->attestation !== 1) {
                    throw new FQException('您还未完成实名认证～', 5101);
                }

                // 已实名并且未成年限制操作
                $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
                if($isUnderAge){
                    throw new FQException('未满18周岁用户暂不支持此功能', 500);
                }

                // 获取用户资产
                $userAsset = $user->getAssets();
                $diamond = $userAsset->getDiamond($timestamp);
                if ($diamond->balance($timestamp) < $diamondCount) {
                    throw new FQException('钻石不够', 2004);
                }

                $beanCount = intval($diamondCount / config('config.scale'));
                $biEvent = BIReport::getInstance()->makeDiamondExchangeBeanBIEvent($diamondCount, $beanCount);

                // 减钻石
                $diamond->exchange($diamondCount, $timestamp, $biEvent);
                $bean = $userAsset->getBean($timestamp);
                $bean->add($beanCount, $timestamp, $biEvent);

                event(new DiamondExchangeBeanDomainEvent($user, $diamondCount, $bean->balance($timestamp),$diamond->balance($timestamp), $timestamp));

                return [$bean, $diamond];
            });
        } catch (Exception $e) {
            Log::error(sprintf('WalletService::diamondExchangeBeanImpl userId=%d $diamondCount=%d ex=%d:%s',
                $userId, $diamondCount, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 工会代充实现
     * @throws FQException
     */
    public function tradeUnionAgent($uid, $toUid, $exchangeDiamond)
    {
        $isHavePermission = DaiChongModelDao::getInstance()->getPermission($uid);
        if (!$isHavePermission) {
            throw new FQException('用户权限不足', 500);
        }
        if ($exchangeDiamond <= 0) {
            throw new FQException('钻石数必须大于0', 500);
        }
//        注销申请中状态下的用户账号禁止进行公众号充值和公会代充值
        $userModel = UserModelDao::getInstance()->loadUserModel($toUid);
        if ($userModel && $userModel->cancelStatus != 0) {
            throw new FQException('对方账号已注销或申请注销中，无法充值', 401);
        }
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($uid);
        if($isUnderAge){
            throw new FQException('您未满18周岁暂不支持此功能', 500);
        }
        // 已实名并且未成年限制操作
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($toUid);
        if($isUnderAge){
            throw new FQException('对方未满18周岁暂不支持此功能', 500);
        }
        $timestamp = time();

        $orderId = CommonUtil::createOrderNo($uid);
        $diamond = $this->tradeUnionAgentDiamondImpl($uid, $toUid, $exchangeDiamond, $timestamp, $orderId);
        $beanNum = $this->tradeUnionAgentCoinImpl($uid, $toUid, $exchangeDiamond, $timestamp, $orderId);

        event(new TradeUnionAgentEvent($uid, $toUid, $exchangeDiamond, $beanNum, $timestamp));

        Log::info(sprintf('DiamondExchangeBean ok userId=%d toUid=%d count=%d beanBalance=%d diamondBalance=%d',
            $uid, $toUid, $exchangeDiamond, $beanNum, $diamond->balance($timestamp)));
        return [
            $beanNum,
            $diamond->balance($timestamp),
        ];
    }

    private function tradeUnionAgentDiamondImpl($uid, $toUid, $exchangeDiamond, $timestamp, $orderId)
    {
        try {
            $redisService = [[config('config.redis.host'), config('config.redis.port'), 0.1]];
            $redisLock = new RedisLock($redisService);
            $lockKey = 'redis_lock_' . $uid;
            $lockRes = $redisLock->lock($lockKey, 3000);
            if (!$lockRes) {
                throw new FQException('操作过快,请重试', 500);
            }

            $diamond = Sharding::getInstance()->getConnectModel('userMaster', $uid)->transaction(function() use($uid, $toUid, $exchangeDiamond, $timestamp, $orderId) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($uid);
                if ($user == null) {
                    throw new FQException('参数错误', 5000);
                }
                if (empty($user->getUserModel()->username)) {
                    throw new FQException('您还没有绑定手机号', 5100);
                }

                // 获取用户资产
                $userAsset = $user->getAssets();
                $diamond = $userAsset->getDiamond($timestamp);
                if ($diamond->balance($timestamp) < $exchangeDiamond) {
                    throw new FQException('钻石不足', 2004);
                }
                $biEvent = BIReport::getInstance()->makeTradeUnionAgentBIEvent($toUid, $exchangeDiamond, $orderId);
                // 减钻石
                $diamond->exchange($exchangeDiamond, $timestamp, $biEvent);
                return $diamond;
            });

            $redisLock->unlock($lockRes);
            return $diamond;
        } catch (Exception $e) {
            Log::error(sprintf('WalletService::tradeUnionAgentDiamondImpl userId=%d toUid=%d $diamondCount=%d ex=%d:%s',
                $uid, $toUid, $exchangeDiamond, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    private function tradeUnionAgentCoinImpl($uid, $toUid, $exchangeDiamond, $timestamp, $orderId)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $toUid)->transaction(function() use($uid, $toUid, $exchangeDiamond, $timestamp, $orderId) {
                $exchangeCoin = $exchangeDiamond / config('config.scale');
                $user = UserRepository::getInstance()->loadUser($toUid);
                if ($user == null) {
                    throw new FQException('参数错误', 5000);
                }
                // 获取用户资产
                $userAsset = $user->getAssets();
                $biEvent = BIReport::getInstance()->makeTradeUnionAgentBIEvent($uid, $exchangeCoin, $orderId);
                $bean = $userAsset->getBean($timestamp);
                $bean->add($exchangeCoin, $timestamp, $biEvent);

                // 累加用户冲豆的历史总值,用户召回需要使用;
                MemberDetailModelDao::getInstance()->incrUserAmount($toUid, $exchangeCoin);
                return $exchangeCoin;
            });
        } catch (Exception $e) {
            Log::error(sprintf('WalletService::tradeUnionAgentCoinImpl userId=%d toUid=%d $diamondCount=%d ex=%d:%s',
                $uid, $toUid, $exchangeDiamond, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

//    /**
//     * @Info 获取用户当前的所有资产数据
//     * @param $userId
//     * @return array
//     */
//    public function getWallet($userId)
//    {
//        $curTime = time();
//        $user = UserRepository::getInstance()->loadUser($userId);
//        $bean = $this->getBean($user, $curTime);
//        $diamond = $this->getDiamond($user, $curTime);
//        $coin = $this->getCoin($user, $curTime);
//        $active = $this->getActive($user, $curTime);
//        return [$diamond, $bean, $coin, $active];
//    }

    public function getWallet($userId)
    {
        $coin = CoinDao::getInstance()->loadCoin($userId);
        $beanObj = BeanModelDao::getInstance()->loadBean($userId);
        $bean = $beanObj ? $beanObj->balance() : 0;
        $diamondObj = DiamondModelDao::getInstance()->loadDiamond($userId);
        $diamond = $diamondObj ? $diamondObj->balance() : 0;
        return [
            'diamond' => $this->coveUnit($diamond, config('config.khd_scale')),
            'bean' => $this->coveUnit($bean, 1),
            'coin' => $this->coveUnit($coin, 1),
        ];
    }

    private function coveUnit($diamond, $unit)
    {
        return [
            'value' => formatRound($diamond),
            'unit' => $unit,
        ];
    }

    private function getActive($user, $curTime)
    {
        $object = $user->getAssets()->getActiveDegree($curTime);
        return $object->balance($curTime);
    }

    private function getBean($user, $curTime)
    {
        $object = $user->getAssets()->getBean($curTime);
        return $object->balance($curTime);
    }

    private function getDiamond($user, $curTime)
    {
        $object = $user->getAssets()->getDiamond($curTime);
        return $object->balance($curTime);
    }

    private function getCoin($user, $curTime)
    {
        $object = $user->getAssets()->getCoin($curTime);
        return $object->balance($curTime);
    }


    /**
     * @Info 用户的今日收益
     * @param $userId
     * @return int|mixed
     */
    public function getTodayEarnings($userId)
    {
        $timestamp = time();
        $todayEarnings = TodayEarningsModelDao::getInstance()->loadTodayEarnings($userId);
        if (empty($todayEarnings)) {
            return 0;
        }
        $todayEarnings->adjust($timestamp);
        return $todayEarnings->diamond;
    }


    /**
     * @info 获取用户资源说明
     * @param $source
     * @return string
     */
    public function initScaleDoc($source)
    {
        switch ($source) {
            case 'yinlian':
                return '* 1钻石=10音豆，只能填写大于等于1的整数';
            case 'fanqie':
                return '* 1钻石=10豆，只能填写大于等于1的整数';
            default:
                return '* 1钻石=10豆，只能填写大于等于1的整数';
        }
    }


    private function getAssetEvent()
    {
        return [
            BIConfig::$DIAMOND_EXCHANGE_EVENTID => 'changes', //充值
            BIConfig::$REDPACKETS_EVENTID => 'getRedPackets', //红包
            BIConfig::$RECEIVE_GIFT_EVENTID => 'other',  //收礼
            BIConfig::$REPLACE_CHARGE_EVENTID => 'guildCharge',  //工会充值
        ];
    }


    public function getUserAsset($userId, $assetId, $operatorId)
    {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $assetId, $operatorId) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    Log::warning(sprintf('WalletService::getUserAsset UserNotExists userId=%d assetId=%s operatorId=%d',
                        $userId, $assetId, $operatorId));
                    throw new FQException('用户不存在', 500);
                }

                // 获取用户资产
                $timestamp = time();
                $userAsset = $user->getAssets();
                $balance = $userAsset->balance($assetId, $timestamp);

                Log::info(sprintf('WalletService::getUserAsset ok userId=%d assetId=%s operatorId=%d balance=%d',
                    $userId, $assetId, $operatorId, $balance));
                return $balance;
            });
        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('WalletService::getUserAsset exception userId=%d assetId=%s operatorId=%d ex=%d:%s trace=%s',
                    $userId, $assetId, $operatorId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }

    public function adjustAsset($userId, $assetId, $change, $operatorId, $reason, $activity, $source='')
    {
        if (!is_integer($change) || $change == 0) {
            Log::warning(sprintf('WalletService::adjustAsset BadChange userId=%d assetId=%s change=%d operatorId=%d',
                $userId, $assetId, $change, $operatorId));
            throw new FQException('变化值错误', 500);
        }
        if ($change > 1000000000) {
            Log::warning(sprintf('WalletService::adjustAsset LimitChange userId=%d assetId=%s change=%d operatorId=%d',
                $userId, $assetId, $change, $operatorId));
            throw new FQException('变化值超限', 500);
        }
        try {

            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $assetId, $change, $operatorId, $reason, $activity, $source) {
                // loadUser会锁住用户
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    Log::warning(sprintf('WalletService::adjustAsset UserNotExists userId=%d assetId=%s change=%d operatorId=%d',
                        $userId, $assetId, $change, $operatorId));
                    throw new FQException('用户不存在', 500);
                }

                // 获取用户资产
                $timestamp = time();
                $userAsset = $user->getAssets();
                $balance = $userAsset->balance($assetId, $timestamp);
                if ($change < 0 && $balance + $change < 0) {
                    Log::warning(sprintf('WalletService::adjustAsset AssetNotEnough userId=%d assetId=%s change=%d operatorId=%d',
                        $userId, $assetId, $change, $operatorId));
                    throw new FQException('资产不足', 500);
                }
                if ($activity) {
                    $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, $activity);
                } else {
                    $biEvent = BIReport::getInstance()->makeGMAdjustBIEvent($operatorId, $reason);
                }
                if ($change > 0) {
                    $balance = $userAsset->add($assetId, $change, $timestamp, $biEvent);
                } else {
                    $balance = $userAsset->consume($assetId, -$change, $timestamp, $biEvent, $source);
                }
                Log::info(sprintf('WalletService::adjustAsset ok userId=%d assetId=%s change=%d operatorId=%d balance=%d',
                    $userId, $assetId, $change, $operatorId, $balance));
                return $balance;
            });

        } catch (Exception $e) {
            if (!($e instanceof FQException)) {
                Log::error(sprintf('WalletService::adjustAsset exception userId=%d assetId=%s change=%d operatorId=%d ex=%d:%s trace=%s',
                    $userId, $assetId, $change, $operatorId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            }
            throw $e;
        }
    }
}