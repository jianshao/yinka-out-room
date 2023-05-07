<?php

namespace app\domain\activity\zhuawawa;

use app\core\mysql\Sharding;
use app\domain\activity\common\service\ActivityService;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\game\poolbase\RunningRewardPool;
use app\domain\user\UserRepository;
use app\service\LockService;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use Exception;
use think\facade\Log;

class ZhuawawaService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ZhuawawaService();
        }
        return self::$instance;
    }

    public function buildKey()
    {
        return "zhuawawa_pool";
    }

    public function buildActivityType()
    {
        return "zhuawawa";
    }

    /**
     * @Info 活动是否开启
     * @param null $timestamp
     * @return bool true 活动中， fasle 未开始或结束
     */
    public function isAction($userId, $timestamp = null)
    {
        if (config('config.appDev') === "dev") {
            return true;
        }

        $config = Config::loadConf();
        $timestamp = $timestamp == null ? time() : $timestamp;
        $startTime = TimeUtil::strToTime($config['startTime']);
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp < $startTime && !ActivityService::getInstance()->checkUserEnable($userId)) {
            throw new FQException("活动没有开始", 513);
        }
        if ($timestamp > $stopTime && !ActivityService::getInstance()->checkUserEnable($userId)) {
            throw new FQException("活动已经结束了", 513);
        }
        return true;
    }

    /**
     * @param null $timestamp
     * @return bool
     * @throws FQException
     */
    public function isEnd($timestamp = null)
    {
        if (config("config.appDev") === "dev") {
            return true;
        }
        $config = Config::loadConf();
        $timestamp = $timestamp == null ? time() : $timestamp;
        $stopTime = TimeUtil::strToTime($config['stopTime']);
        if ($timestamp < $stopTime) {
            throw new FQException("活动没有结束", 500);
        }
        return true;
    }

    /**
     * @info 砸蛋
     * @param $userId
     * @param $number
     * @param $timestamp
     * @return array
     * @throws FQException
     */
    public function fire($userId, $number, $timestamp)
    {
//        用户扣钱
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $number, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user === null) {
                    throw new FQException("用户不存在", 500);
                }
                if ($user->getAssets()->balance(AssetKindIds::$COIN, $timestamp) < $number) {
                    throw new FQException("金币不足", 500);
                }
                $consumeNumber = 1000 * $number;
                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, $this->buildActivityType(), 'fire');
                $re = $user->getAssets()->consume(AssetKindIds::$COIN, $consumeNumber, $timestamp, $biEvent);
                Log::info(sprintf('ZhuawawaService.fire ok userId=%d fire number=%d timestamp:%d cuonsume re:%d', $userId, $number, $timestamp, $re));
            });

        } catch (Exception $e) {
            throw $e;
        }
//        砸蛋
        $rewardPool = new ZhuawawaReward();
        $jsonData = $this->getJsonReward();
        $rewardPool->fromJson($jsonData);
        $poolConfStr = $rewardPool->encodeToDaoRedisJson();
        $key = $this->buildKey();
        list($giftMap, $breakReGiftId) = RunningRewardPool::breakGift($key, $rewardPool->poolId, $number, $poolConfStr, null);
        Log::info(sprintf('ZhuawawaService.fire rewardPool ok userId=%d number=%d timestamp:%d poolConfStr:%s result-giftMap:%s breakReGiftId:%d', $userId, $number, $timestamp, $poolConfStr, json_encode($giftMap), $breakReGiftId));
        return [$giftMap, $breakReGiftId];
    }


    /**
     * @info 发奖励
     * @param $userId
     * @param $giftMap
     * @return array
     * @throws FQException
     */
    public function adjustAssets($userId, $giftMap)
    {
        $timestamp = time();
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $giftMap, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user === null) {
                    throw new FQException("用户不存在", 500);
                }
                $result = [];
                foreach ($giftMap as $giftId => $count) {
                    if ($giftId === 1) {
                        continue;
                    }
                    $itemDeliveryAssets = ZhuawawaSystem::getInstance()->getPropForId($giftId);
                    $deliveryAssetItem = $itemDeliveryAssets['deliveryAsset'];
                    $label = '';
                    if ($deliveryAssetItem['assetId'] === AssetKindIds::$BEAN) {
                        $assetKind = AssetSystem::getInstance()->findAssetKind($deliveryAssetItem['assetId']);
                        if ($assetKind == null) {
                            throw new FQException("资产不存在", 500);
                        }
                    }
                    if (!empty($assetKind)) {
                        $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, $this->buildActivityType(), 'fire');
                        $itemPrice=$itemDeliveryAssets['price']??0;
                        $allPrice=$itemPrice*$count;
                        $user->getAssets()->add($deliveryAssetItem['assetId'], $allPrice, $timestamp, $biEvent);
                    }
                    $displayName = $itemDeliveryAssets['desc'] ?? "";
                    $itemData = [
                        'name' => $displayName,
                        'image' => CommonUtil::buildImageUrl($assetKind->image),
                        'label' => $label,
                        'count' => $count
                    ];
                    $result[] = $itemData;
                }

                return $result;
            });
        } catch (\Exception $e) {
            Log::info(sprintf('ZhuawawaService.adjustAssets error userId=%d giftMap=%s error msg:%s:strace:%s', $userId, json_encode($giftMap), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }

    }

    /**
     * @return array
     * @throws FQException
     */
    private function getJsonReward()
    {
        return ZhuawawaSystem::getInstance()->getRewardPool();
    }


    /**
     * @param $userId
     * @param $number
     * @param $timestamp
     * @throws FQException
     */
    public function addCoin($userId, $number, $timestamp)
    {
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $number, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user === null) {
                    throw new FQException("用户不存在", 500);
                }

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, $this->buildActivityType(), 'fire', $number);
                $re = $user->getAssets()->add(AssetKindIds::$COIN, $number, $timestamp, $biEvent);
                Log::info(sprintf('ZhuawawaService.addCandy ok userId=%d fire number=%d timestamp:%d cuonsume re:%d', $userId, $number, $timestamp, $re));
                return $re;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $userId
     * @param $timestamp
     * @return array
     * @throws FQException
     */
    public function init($userId, $timestamp)
    {
        //        获取配置的签到数据
        $lockKey = ZhuawawaUserDao::getInstance()->buildLockKey($userId, $timestamp);
        LockService::getInstance()->lock($lockKey);
        try {
//            2.今日累计充值
            $bankAccount = ZhuawawaUserDao::getInstance()->loadUser($userId);
            if (empty($bankAccount)) {
                throw new FQException("用户不存在", 500);
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
        return $bankAccount;
    }

    /**
     * @return array
     * @throws FQException
     */
    public function loadIndexList()
    {
        return ZhuawawaSystem::getInstance()->loadPropData();
    }


}