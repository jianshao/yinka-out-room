<?php


namespace app\domain\game\taojin;


use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIReport;
use app\domain\exceptions\AssetNotEnoughException;
use app\domain\exceptions\AssetNotEnoughException2;
use app\domain\exceptions\FQException;
use app\domain\game\GameService;
use app\domain\game\taojin\dao\TaoJinRankModelDao;
use app\domain\game\taojin\dao\TaoJinRewardModelDao;
use app\domain\game\taojin\model\TaoJinRewardModel;
use app\domain\mall\dao\MallBuyRecordModelDao;
use app\domain\mall\MallIds;
use app\domain\mall\service\MallService;
use app\domain\user\UserRepository;
use app\event\TaoJinRewardEvent;
use app\utils\ArrayUtil;
use think\facade\Log;
use Exception;

class TaojinService
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TaojinService();
        }
        return self::$instance;
    }

    public function setSelfDiceNum($userId, $gameId, $num){
        try {
            if ($num > 50 || $num < 6) {
                throw new FQException('请输入6-50的整数', 500);
            }
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->set('self_num_game_' . $userId . '_' . $gameId, $num);
        }catch (Exception $e) {
            Log::error(sprintf('TaojinService setDiceNum userId=%d $gameId=%d count=%d',
                    $userId, $gameId, $num));
            throw $e;
        }
    }

    public function getSelfDiceNum($userId, $gameId){
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->get('self_num_game_' . $userId . '_' . $gameId);
    }

    public function setRankData($userId, $taojinRewards, $timestamp){
        $total = 0;
        foreach ($taojinRewards as list($diceNum, $taojinReward)){
            if ($taojinReward->reward->assetId != AssetKindIds::$BEAN) {
                continue;
            }

            $total += $taojinReward->reward->count;
        }

        TaoJinRankModelDao::getInstance()->setRankData($userId, $total, $timestamp);
        TaoJinRankModelDao::getInstance()->removeLastRank($timestamp);
    }

    public function doRollDice($userId, $gameId, $count, $autoBuy){
        $timestamp = time();

        //收费
        list($balance, $taojinGame) = $this->collectFee($userId, $gameId, $count, $timestamp, $autoBuy);

        //摇骰子
        $taojinRewards = $this->rollDice($userId, $count, $taojinGame);

        //摇骰子奖励
        $this->sendReward($userId, $taojinGame, $taojinRewards, $count, $timestamp);

        $this->setRankData($userId, $taojinRewards, $timestamp);

        event(new TaoJinRewardEvent($userId, $gameId, $taojinRewards, $timestamp));
        return $taojinRewards;
    }

    public function collectFee($userId, $gameId, $count, $timestamp, $autoBuy){
        $taojinGame = TaojinSystem::getInstance()->findTaojinByGameId($gameId);
        if ($taojinGame == null) {
            throw new FQException('当前游戏已关闭', 500);
        }
        $totalPrice = $taojinGame->energy * $count;
        list($consume, $balance) = $this->tryCollectFee($userId, 0, $gameId, $count, $totalPrice, $autoBuy, $timestamp);
        if ($consume >= $totalPrice) {
            // 扣费成功
            return [$balance, $taojinGame];
        }

        // 费用不足
        if (!$autoBuy) {
            throw new AssetNotEnoughException2(GameService::getInstance()->priceAssetId, '积分不足', 211);
        }

        // 计算需要购买商品数量
        $rem = $totalPrice - $balance;
        $goods = GameService::getInstance()->getGoods();
        $countPerGoods = $goods->deliveryAsset->count;
        $goodsCount = intval(($rem + ($countPerGoods - 1)) / $countPerGoods);

        // 购买商品
        try {
            MallService::getInstance()->buyGoodsByGoods($userId, $goods, $goodsCount, MallIds::$GAME, 'taojin');
        } catch (AssetNotEnoughException $e) {
            throw new AssetNotEnoughException2(AssetKindIds::$BEAN, '积分不足', 211);
        }

        list($consume, $balance) = $this->tryCollectFee($userId, 0, $gameId, $count, $totalPrice, $autoBuy, $timestamp);
        if ($consume >= $totalPrice) {
            // 扣费成功
            return [$balance, $taojinGame];
        }

        throw new AssetNotEnoughException2(GameService::getInstance()->priceAssetId, '积分不足', 211);
    }

    private function tryCollectFee($userId, $roomId, $gameId, $count, $totalPrice, $autoBuy, $timestamp) {
        $biEvent = BIReport::getInstance()->makeActivityBIEvent($roomId, 'taojin', $gameId, $count);
        return AssetUtils::consumeAsset($userId, GameService::getInstance()->priceAssetId, $totalPrice, $timestamp, $biEvent);
    }

    /**
     * @param $userId
     * @param $count
     * @param $taojinGame TaoJin
     * @return array
     * @throws Exception
     */
    public function rollDice($userId, $count, $taojinGame){
        try {
            // 摇骰子发奖励
            $taojinRewards = [];
            $step = $this->getStep($userId, $taojinGame->gameId);
            $step = $step ? $step : 0; //从第一步开始
            for ($i = 0; $i < $count; $i++){
                //奖励是从当前位置加1，往后算的，位置从0起步
                list($diceNum, $taojinReward) = $this->randDice($step + 1, $taojinGame->rewardList);

                $step += $diceNum;
                $taojinRewards[] = array($diceNum, $taojinReward);

                Log::info(sprintf('TaojinService rollDice userId=%d gameId=%d, i=%d, step=%d, diceNum=%d reward=%s:%d',
                    $userId, $taojinGame->gameId, $i, $step, $diceNum, $taojinReward->reward->assetId, $taojinReward->reward->count));
            }

            $step = $step % count($taojinGame->rewardList);
            $this->setStep($userId, $taojinGame->gameId, $step);
            return $taojinRewards;
        } catch (Exception $e) {
            Log::error(sprintf('TaojinService rollDice userId=%d $gameId=%d count=%d',
                $userId, $taojinGame->gameId, $count));
            throw $e;
        }
    }

    /**
     * @param $userId
     * @param $taojinGame TaoJin
     * @param $taojinRewards
     * @param $timestamp
     * @return mixed
     * @throws FQException
     */
    public function sendReward($userId, $taojinGame, $taojinRewards, $count, $timestamp){
        try {
            $taoJinRewardModels = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $taojinRewards, $taojinGame, $count, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $biEvent = BIReport::getInstance()->makeActivityBIEvent(0, "taojin", $taojinGame->gameId, $count);
                $rewards = [];
                $taoJinRewardModels = [];
                foreach ($taojinRewards as list($diceNum, $taojinReward)){
                    $assetId = $taojinReward->reward->assetId;
                    $count = $taojinReward->reward->count;
                    $rewards[$assetId] = array_key_exists($assetId, $rewards) ? $rewards[$assetId]+$count : $count;

                    $type = $this->getTypeFromAssetId($assetId);
                    $model = new TaoJinRewardModel($userId, $taojinGame->gameId, $type, $count, $timestamp);
                    $taoJinRewardModels[] = $model;
                }

                foreach ($rewards as $assetId => $count){
                    $user->getAssets()->add($assetId, $count, $timestamp, $biEvent);
                }

                Log::info(sprintf('TaojinService sendReward userId=%d gameId=%d, reward=%s',
                    $userId, $taojinGame->gameId, \GuzzleHttp\json_encode($rewards)));

                return $taoJinRewardModels;
            });

            Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function() use($taoJinRewardModels) {
                foreach ($taoJinRewardModels as $taoJinRewardModel){
                    TaoJinRewardModelDao::getInstance()->saveReward($taoJinRewardModel);
                }
            });

        } catch (Exception $e) {
            Log::error(sprintf('TaojinService sendReward userId=%d $gameId=%d ex=%d:%s',
                $userId, $taojinGame->gameId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    //淘金兑换存数据库的key
    public function getExchangeFromKey(){
        return MallBuyRecordModelDao::$TAOJIN_EXCHANGE;
    }

    /**
     * @param $step int 当前位置
     * @param $totalReward array 总奖励
     * @return mixed|null
     * @throws Exception
     */
    private function randDice($step, $totalReward) {
        $rewardList = [];
        $total = 0;
        for ($n = 0; $n < 6; $n++) {
            $i = ($step + $n) % count($totalReward);
            $rewardList[] = $totalReward[$i];
        }

        foreach ($rewardList as $reward){
            $total += $reward->weight;
        }
        $value = random_int(1, $total);

        Log::info(sprintf('TaojinService::randDice step=%d rewardCount=%d totalRewardCount=%d total=%d rand=%d',
            $step, count($rewardList), count($totalReward), $total, $value));

        $diceNum = 0;
        $curtotal = 0;
        foreach ($rewardList as $reward) {
            $diceNum += 1;
            $curtotal += $reward->weight;
            if ($value <= $curtotal) {
                return array($diceNum, $reward);
            }
        }

        Log::error(sprintf('TaojinService randDice step=%d, $diceNum=%d randnum=%d',
            $step, $diceNum, $value));
        throw new FQException('摇骰子错误', 500);
    }

    public function getStep($userId, $gameId){
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->get('game_step_' . $gameId . '_' . $userId);
    }

    public function setStep($userId, $gameId, $step){
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->set('game_step_' . $gameId . '_' . $userId, $step);
    }

    //1化石 2金 3银4铁 5豆
    public function getTypeFromAssetId($assetId) {
        $oreType = [
            AssetKindIds::$BEAN => 5,
            AssetKindIds::$TAOJIN_ORE_IRON => 4,
            AssetKindIds::$TAOJIN_ORE_SILVER => 3,
            AssetKindIds::$TAOJIN_ORE_GOLD => 2,
            AssetKindIds::$TAOJIN_ORE_FOSSIL => 1,
        ];

        return ArrayUtil::safeGet($oreType, $assetId);
    }

    //1化石 2金 3银4铁 5豆
    public function getAssetIdFromType($type) {
        $oreType = [
            1 => AssetKindIds::$TAOJIN_ORE_FOSSIL,
            2 => AssetKindIds::$TAOJIN_ORE_GOLD,
            3 => AssetKindIds::$TAOJIN_ORE_SILVER,
            4 => AssetKindIds::$TAOJIN_ORE_IRON,
            5 => AssetKindIds::$BEAN
        ];

        return ArrayUtil::safeGet($oreType, $type);
    }
}