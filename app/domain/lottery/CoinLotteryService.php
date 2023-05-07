<?php

namespace app\domain\lottery;


use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\utils\ArrayUtil;
use think\facade\Log;


/**
 * 金币抽奖
 */
class CoinLotteryService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CoinLotteryService();
        }
        return self::$instance;
    }

    /**
     * @deprecated
     */
    public function coinLottery($userId, $num) {
        if ($num <= 0) {
            throw new FQException('抽奖次数错误', 500);
        }

        $price = CoinLotterySystem::getInstance()->getPrice($num);
        if ($price === null) {
            throw new FQException('没有该次抽奖', 500);
        }

        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $price, $num) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                // 判断抽奖的余额
                $timestamp = time();
                $totalPrice = $num * $price->count;
                $userAssets = $user->getAssets();
                if ($userAssets->balance($price->assetId, $timestamp) < $totalPrice) {
                    throw new AssetNotEnoughException('余额不足', 500);
                }

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'coin_lottery', '', $num);
                $balance = $userAssets->consume($price->assetId, $totalPrice, $timestamp, $biEvent);

                $lotterys = [];
                $biRewardEvent = BIReport::getInstance()->makeActivityBIEvent(0, 'coin_lottery', '', $num);
                for ($i = 0; $i < $num; $i++){
                    $lottery = $this->randLottery();
                    $userAssets->add($lottery->reward->assetId, $lottery->reward->count, $timestamp, $biRewardEvent);

                    $lotterys[] = $lottery;

                    Log::info(sprintf('CoinLotteryService coinLottery ok userId=%d num=%d, i=%d, consume=%s:%d:%d reward=%s:%d',
                        $userId, $num, $i, $price->assetId, $price->count, $balance,
                        $lottery->reward->assetId, $lottery->reward->count));
                }

                foreach ($lotterys as $lottery){
                    list($type, $rewardId) = $this->getRewardType($lottery->reward->assetId);
                    $model = new CoinLotteryRewardModel($userId, $rewardId, $type, $lottery->reward->count, $timestamp);
                    CoinLotteryRewardModelDao::getInstance()->saveReward($model);
                }

                return [$balance, $lotterys];
            });

        } catch (Exception $e) {
            throw $e;
        }
    }

    /*
     * return Lottery
     * */
    private function randLottery() {
        $total = CoinLotterySystem::getInstance()->getLotteryTotalWeight();
        $value = random_int(1, $total);
        $lotterys = CoinLotterySystem::getInstance()->getLotterys();

        $curtotal = 0;
        foreach ($lotterys as $lottery) {
            $curtotal += $lottery->weight;
            if ($value <= $curtotal) {
                return $lottery;
            }
        }

        return null;
    }

    //1头像，气泡框 2金币 3礼物
    public function getRewardType($assetId) {
        if($assetId == AssetKindIds::$COIN){
            return array(2, '');
        }elseif (AssetUtils::isGiftAsset($assetId)){
            return array(3, AssetUtils::getGiftKindIdFromAssetId($assetId));
        }elseif (AssetUtils::isPropAsset($assetId)){
            return array(1, AssetUtils::getPropKindIdFromAssetId($assetId));
        }

        throw new FQException('没有定义该资产类型', 500);
    }

    //1头像，气泡框 2金币 3礼物
    public function getAssetId($type, $rewardId) {
        $rewardTypes = [
            1 => AssetUtils::makePropAssetId($rewardId),
            2 => AssetKindIds::$COIN,
            3 => AssetUtils::makeGiftAssetId($rewardId)
        ];

        return ArrayUtil::safeGet($rewardTypes, (int)$type);
    }
}

