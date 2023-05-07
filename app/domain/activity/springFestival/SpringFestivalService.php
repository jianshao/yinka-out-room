<?php


namespace app\domain\activity\springFestival;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\activity\common\service\ActivityService;
use app\domain\activity\halloween\HalloweenReward;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\game\poolbase\RunningRewardPool;
use app\domain\led\LedService;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\service\WalletService;
use app\domain\user\UserRepository;
use app\service\LockService;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class SpringFestivalService
{
    protected static $instance;
    protected $userBankKey = 'user:spring:activity:bank:%s';

    public $config = null;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SpringFestivalService();
        }
        return self::$instance;
    }

    public function isRunning($userId, $timestamp = null)
    {
        $config = Config::loadConf();
        $timestamp = $timestamp == null ? time() : $timestamp;
        $startTime = TimeUtil::strToTime($config['startTime']);
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if (($timestamp >= $startTime && $timestamp <= $stopTime) || ActivityService::getInstance()->checkUserEnable($userId)) {
            return [true, $config];
        }
        return [false, $config];
    }

    public function incrPool($activityName, $activityType, $num, $config)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $unitPrice = $this->getActivityUnitPrice($activityName, $activityType);
        $allCountPrice = $unitPrice * $num * $config['rate'];     //总价值
        $luck_star_pool_value = $redis->incrBy($config['poolKey'], $allCountPrice);
        Log::info(sprintf('福气奖池更新后:%s', $luck_star_pool_value));
        return true;
    }

    public function getGoldBarCount($userId, $config)
    {
        $goldBarInfo = SpringFestivalService::getInstance()->getUserBankInfo($userId, $config, 'goldBarArea');
        $goldBar = current($goldBarInfo);
        return intval($goldBar['giftCount'] ?? 0);
    }

    public function extractCouplet($activityName, $activityType, $count, $config, $userId)
    {
        $giftMap = $this->takeCoupletImpl($activityName, $activityType, $config, $count);
        $this->deliveryGifts($giftMap, $userId, $config);
    }

    public function deliveryGifts($rewardGiftLists, $userId, $config)
    {
        if (!empty($rewardGiftLists)) {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
            $userBankKey = $this->buildBankKey($userId);
            $userBank = $config['userBank'];
            $buildMsg = "";
            foreach ($rewardGiftLists as $giftId => $count) {
                $redis->hIncrBy($userBankKey, $giftId, $count);
                Log::info(sprintf('SpringFestivalService deliveryGifts userId:%s giftId:%s count:%s reason:%s', $userId, $giftId, $count, '活动额外产出'));
                $buildMsg = $buildMsg. "“" . $userBank[$giftId] . "”*$count";
            }
            $msg = "恭喜获得了".$buildMsg."，您可在小音送福活动页面查看。";
            Log::info('deliveryGifts msg'. $msg);
            YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
        }
    }

    public function takeCoupletImpl($activityName, $activityType, $config, $count)
    {
        $activityFallingChance = $config['activityFallingChance'] ?? null;
        $key = $this->buildRunningKey();
        $rewardPool = new HalloweenReward();
        $jsonData = ArrayUtil::safeGet($config, 'rewardPool');
        $rewardPool->fromJson($jsonData);
        $poolConfStr = $rewardPool->encodeToDaoRedisJson();
        $ret = [];
        if (!empty($activityFallingChance)) {
            $breakCount = 0;
            for ($i = 1; $i <= $count; $i++) {
                //判断每次的概率
                if ($this->isLuck($activityName, $activityType, $activityFallingChance)) {
                    $breakCount++;
                }
            }
            if ($breakCount > 0) {
                //抽取对联池中的字
                list($giftMap, $breakReGiftId) = RunningRewardPool::breakGift($key, $rewardPool->poolId, $breakCount, $poolConfStr, null);
                $ret = $giftMap;
            }
        }
        return $ret;
    }

    public function getUserBankInfo($userId, $config, $area)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
        $userBankKey = $this->buildBankKey($userId);
        $areaList = $config[$area];
        $bankList = $config['userBank'];
        $userBankList = $redis->hGetAll($userBankKey);
        $userAllBankList = [];
        if (empty($userBankList)) {
            foreach ($areaList as $giftId) {
                $giftInfo = [];
                $giftInfo['giftId'] = $giftId;
                $giftInfo['giftName'] = $bankList[$giftId];
                $giftInfo['giftCount'] = 0;
                $userAllBankList[] = $giftInfo;
            }
            return $userAllBankList;
        }
        foreach ($areaList as $giftId) {
            $giftInfo = [];
            $giftInfo['giftId'] = $giftId;
            $giftInfo['giftName'] = $bankList[$giftId];
            $giftCount = intval($userBankList[$giftId] ?? 0);
            $giftInfo['giftCount'] = $giftCount < 0 ? 0 : $giftCount;
            $userAllBankList[] = $giftInfo;
        }
        return $userAllBankList;
    }

    public function buildExchangeRules($rules, $userBankCoupletInfo)
    {
        $tempUserBankCoupletInfo = array_column($userBankCoupletInfo, null, 'giftId');
        foreach ($rules as &$rule) {
            $rule['isCanExchange'] = $this->isCanExchange($rule, $tempUserBankCoupletInfo);
        }
        return $rules;
    }

    public function isCanExchange($rule, $userBankCoupletInfo)
    {
        if (isset($rule['canConsume'])) {
            $flag = 0;
            foreach ($rule['canConsume'] as $giftId) {
                if ($userBankCoupletInfo[$giftId]['giftCount'] > 0) {
                    $flag = 1;
                    break;
                }
            }
        } else {
            $flag = 1;
            foreach ($rule['consume'] as $giftId) {
                if ($userBankCoupletInfo[$giftId]['giftCount'] == 0) {
                    $flag = 0;
                    break;
                }
            }
        }
        return $flag;
    }


    public function getPoolValue()
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $config = Config::loadConf();
        $poolValue = $redis->get($config['poolKey']);
        if (empty($poolValue)) {
            $poolValue = 0;
        }
        return intval($poolValue / 100);
    }

    /**
     * @throws \app\domain\exceptions\FQException
     * @throws \Exception
     */
    public function exchange($userId, $exchangeId, $consumeId, $config)
    {
        $tempExchangeRules = array_column($config['exchangeRules'], null, 'id');
        if (!isset($tempExchangeRules[$exchangeId])) {
            throw new FQException('兑换物品不存在', 500);
        }
        if ($consumeId > 0) {
            if (!in_array($consumeId, $tempExchangeRules[$exchangeId]['canConsume'])) {
                throw new FQException('兑换条件不成立', 500);
            }
        }
        $lockKey = $this->buildLockKey($userId);
        LockService::getInstance()->lock($lockKey);
        try {
            $this->exchangeConsume($userId, $consumeId, $tempExchangeRules[$exchangeId], $config, -1);
            if ($consumeId > 0) {
                WalletService::getInstance()->adjustAsset($userId, sprintf("prop:%s", $exchangeId), 1, 0, '兑换', 'springFestival');
            } else {
                $this->updateUserBank($userId, [$exchangeId], 1, '兑换增加');
            }
            $this->afterExChange($userId, $exchangeId, $config);
            return rjson([], 200, '兑换成功');
        } catch (FQException $e) {
            Log::error(sprintf('SpringFestivalService exchange Exception userId=%d ex=%d:%s trace=%s',
                $userId, $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
    }

    /**
     * @throws \app\domain\exceptions\FQException
     */
    public function exchangeConsume($userId, $consumeId, $exchangeInfo, $config, $count)
    {
        $userBankCoupletInfo = $this->getUserBankInfo($userId, $config, 'coupletArea');
        $tempUserBankCoupletInfo = array_column($userBankCoupletInfo, null, 'giftId');
        if ($consumeId > 0) {
            if ($tempUserBankCoupletInfo[$consumeId]['giftCount'] < abs($count)) {
                throw new FQException('数量不足', 500);
            }
            $this->updateUserBank($userId, [$consumeId], $count, '兑换消耗');
        } else {
            $giftIds = $exchangeInfo['consume'];
            foreach ($giftIds as $giftId) {
                if ($tempUserBankCoupletInfo[$giftId]['giftCount'] < abs($count)) {
                    throw new FQException('数量不足', 500);
                }
            }
            $this->updateUserBank($userId, $giftIds, $count, '兑换消耗');
        }
    }

    /**
     * Notes: 兑换之后如果是金条，全服跑马灯 ,如果是爆竹记录榜单
     * @param $userId
     * @param $exchangeId
     */
    public function afterExChange($userId, $exchangeId, $config)
    {
        if ($exchangeId == 15) {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
            $bangerSetKey = $this->buildGoldBarKey();
            $redis->zIncrBy($bangerSetKey, 1, $userId);
            LedService::getInstance()->buildGoldBarEvent($userId);
            $msg = "恭喜您！集齐全部对联获得了10g金条*1，请联系客服进行兑换。";
            YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
        }
        if (in_array($exchangeId, $config['bangerArea'])) {
            //更新用户爆竹积分
            $this->updateBangerScore($userId, $exchangeId, 1);
        }
    }

    public function updateBangerScore($userId, $bangerId, $count)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
        $bangerSetKey = $this->buildBangerSetKey($bangerId);
        $bangerStringKey = $this->buildBangerStringScore($bangerId);
        $redis->zIncrBy($bangerSetKey, $count, $userId);
        $redis->incrBy($bangerStringKey, $count);
    }

    public function updateUserBank($userId, $giftIds, $count, $reason)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
        $userBankKey = $this->buildBankKey($userId);
        foreach ($giftIds as $giftId) {
            $redis->hIncrBy($userBankKey, $giftId, $count);
            Log::info(sprintf('SpringFestivalService updateUserBank userId:%s giftId:%s count:%s reason:%s', $userId, $giftId, $count, $reason));
        }
    }


    public function buildLockKey($userId): string
    {
        return "springFestival:lock:user:" . $userId;
    }

    public function buildBangerSetKey($bangerId): string
    {
        return "springFestival:set:score:banger:" . $bangerId;
    }

    public function buildBangerStringScore($bangerId): string
    {
        return "springFestival:string:score:banner:" . $bangerId;
    }

    public function buildBankKey($userId)
    {
        return sprintf($this->userBankKey, $userId);
    }


    public function buildRunningKey()
    {
        return 'couplet_run_pool';
    }

    public function buildGoldBarKey()
    {
        return 'springFestival:set:score:gold';
    }

    /**
     * @param $activeName
     * @param $type
     * @return int
     */
    public function getActivityUnitPrice($activeName, $activityType): int
    {
        $unitPrice = 0;
        if ($activeName === "breakBox") {
            if ($activityType == 1) {
                $unitPrice = 20;
            }
            if ($activityType == 2) {
                $unitPrice = 100;
            }
            if ($activityType == 3) {
                $unitPrice = 600;
            }
        }

        if ($activeName === "turntable") {
            if ($activityType == 1) {
                $unitPrice = 300;
            }
            if ($activityType == 2) {
                $unitPrice = 1000;
            }
        }
        return $unitPrice;
    }

    public function buildRewardGift($giftMap)
    {
        $ret = [];
        foreach ($giftMap as $value) {
            $num = $ret[$value] ?? 0;
            $ret[$value] = $num + 1;
        }
        return $ret;
    }

    public function isLuck($activityName, $activityType, $activityFallingChance)
    {
        $key = sprintf("%s:%s", $activityName, $activityType);
        $chance = $activityFallingChance[$key] ?? 0;
        if ($chance == 0) {
            return false;
        } else {
            $randNum = rand(1, 100);
            return $randNum <= $chance;
        }

    }

    /**
     * Notes: 福气奖池瓜分
     */
    public function blessingPoolPartition()
    {
        $config = Config::loadConf();
        $time = time();
        if ($time > strtotime($config['stopTime']) || config('config.appDev') === "dev") {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 6]);
            $isPartition = $redis->get('spring:festival:partition');
            if ($isPartition == 1) {
                Log::info(sprintf("blessingPoolPartition partitioned time:%s", $time));
                exit();
            }
            $poolValue = $this->getPoolValue();
            $rateList = $config['bangerPartitionRate'];
            $bangerTitles = $config['bangerTitle'];
            foreach ($rateList as $bangerId => $rate) {
                $bangerTitle = $bangerTitles[$bangerId];
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'springFestival', $time, $bangerId);
                $bangerAllScoreKey = $this->buildBangerStringScore($bangerId);
                $allScore = $redis->get($bangerAllScoreKey);
                $bangerSetKey = $this->buildBangerSetKey($bangerId);
                $bangerRank = $redis->zRange($bangerSetKey, 0, -1, true);
                foreach ($bangerRank as $userId => $score) {
                    $addCoin = floor((($poolValue * $rate) / $allScore) * $score);
                    try {
                        Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $addCoin, $time, $biEvent) {
                            $user = UserRepository::getInstance()->loadUser($userId);
                            $bean = $user->getAssets()->getBean($time);
                            $bean->add($addCoin, $time, $biEvent);
                        });
                    } catch (\Exception $e) {
                        Log::info(sprintf("blessingPoolPartition error code:%s message:%s trace:%s", $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
                    }
                    $msg = sprintf("恭喜您！您的“%s”福字获得%s音豆奖励，已放入钱包", $bangerTitle, $addCoin);
                    YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => ['msg' => $msg]]);
                }
            }
            $redis->set('spring:festival:partition',1);
        }
    }


}