<?php


namespace app\domain\game\turntable;


use app\domain\exceptions\FQException;
use app\domain\game\poolbase\RewardPool;
use app\utils\ArrayUtil;
use think\facade\Log;

class Turntable
{
    public $turntableId = '';
    public $name = '';
    public $price = 0;

    // 锦鲤榜
    public $inJinliRankGiftValue = 0;

    // 奖池列表map<poolId, RewardPool>
    public $rewardPoolMap = [];
    // 池子列表 list<Pool>
    public $typedPoolList = [];
    // 池子map<poolType, Pool>
    public $typedPoolMap = [];
    public $specialConf = null;

    public function indexOfPoolType($poolType) {
        for ($i = 0; $i < count($this->typedPoolList); $i++) {
            if ($this->typedPoolList[$i]->poolType == $poolType) {
                return $i;
            }
        }
        return -1;
    }

    public function findRewardPool($poolId) {
        return ArrayUtil::safeGet($this->rewardPoolMap, $poolId);
    }

    public function findTypedPool($poolType) {
        return ArrayUtil::safeGet($this->typedPoolMap, $poolType);
    }

    private function chooseRewardPoolInTypedPool($typedPoolIndex, $boxUser) {
        $typedPool = $this->typedPoolList[$typedPoolIndex];
        $index = 0;
        if ($boxUser->curPoolId != 0) {
            $index = $typedPool->indexOfRewardPool($boxUser->curPoolId);
            if ($index < 0) {
                $index = 0;
            }
        }
        for ($i = $index; $i < count($typedPool->rewardPools); $i++) {
            $rewardPool = $typedPool->rewardPools[$i];
            if ($rewardPool->condition == null) {
                return $rewardPool;
            }
            if ($rewardPool->condition->checkCondition($rewardPool, $boxUser)) {
                return $rewardPool;
            }
        }
        return null;
    }

    /**
     * 选择奖池
     *
     * @param $boxUser
     */
    public function chooseRewardPool($boxUser) {
        // 先看当前所在奖池类型
        $typedPoolIndex = -1;
        if (!empty($boxUser->curPoolType)) {
            $typedPoolIndex = $this->indexOfPoolType($boxUser->curPoolType);
        }

        if ($typedPoolIndex < 0) {
            $typedPoolIndex = 0;
        }

        for ($i = $typedPoolIndex; $i < count($this->typedPoolList); $i++) {
            $rewardPool = $this->chooseRewardPoolInTypedPool($i, $boxUser);
            if ($rewardPool != null) {
                return $rewardPool;
            }
        }

        Log::warning(sprintf('Turntable:chooseRewardPool NotFoundRewardPool userId=%d curPoolType=%s curPoolId=%d',
            $boxUser->userId, $boxUser->curPoolType, $boxUser->curPoolId));

        return $this->typedPoolList[count($this->typedPoolList) - 1]->rewardPools[0];
    }

    public function decodeFromJson($jsonObj) {
        $turntableId = $jsonObj['turntableId'];
        $name = $jsonObj['name'];
        $price = $jsonObj['price'];

        $rewardPoolMap = [];
        $typedPoolMap = [];
        $typedPoolList = [];

        $specialConf = null;

        $rewardPoolConfs = $jsonObj['pools'];
        foreach ($rewardPoolConfs as $rewardPoolConf) {
            $rewardPool = new RewardPool();
            $rewardPool->fromJson($rewardPoolConf);
            if (array_key_exists($rewardPool->poolId, $rewardPoolMap)) {
                Log::error(sprintf('Turntable::decodeFromJson DuplicatePoolId turntableId=%d poolId=%d',
                    $turntableId, $rewardPool->poolId));
                throw new FQException('池子id重复配置错误,poolId='.$rewardPool->poolId, 500);
            }
            if (!PoolTypes::isValid($rewardPool->poolType)) {
                Log::error(sprintf('Turntable::decodeFromJson BadPoolType turntableId=%d poolId=%d poolType=%s',
                    $turntableId, $rewardPool->poolId, $rewardPool->poolType));
                throw new FQException('池子类型重复配置错误,poolType='.$rewardPool->poolType, 500);
            }
            $rewardPoolMap[$rewardPool->poolId] = $rewardPool;
            if (!array_key_exists($rewardPool->poolType, $typedPoolMap)) {
                $typedPool = new TypedPool($rewardPool->poolType);
                $typedPoolMap[$rewardPool->poolType] = $typedPool;
                $typedPoolList[] = $typedPool;
            }
            $typedPoolMap[$rewardPool->poolType]->addRewardPool($rewardPool);
        }

        if ($price <= 0) {
            Log::error(sprintf('Turntable::decodeFromJson BadPrice turntableId=%d price=%d',
                $turntableId, $price));
            throw new FQException('价格配置错误,转盘Id='.$turntableId, 500);
        }

        $inJinliRankGiftValue = ArrayUtil::safeGet($jsonObj, 'inJinliGiftValue', 2000);

        $this->turntableId = $turntableId;
        $this->name = $name;
        $this->price = $price;

        foreach ($typedPoolMap as $poolType => &$typedPool) {
            $typedPool->sortRewardPools();
        }

        usort($typedPoolList, function($a, $b) {
            $aSortValue = ArrayUtil::safeGet(PoolTypes::$POOL_SORT_MAP, $a->poolType, 10000);
            $bSortValue = ArrayUtil::safeGet(PoolTypes::$POOL_SORT_MAP, $b->poolType, 10000);
            if ($aSortValue < $bSortValue) {
                return -1;
            } else if ($aSortValue > $bSortValue) {
                return 1;
            }
            return 0;
        });

        $this->typedPoolMap = $typedPoolMap;
        $this->typedPoolList = $typedPoolList;
        $this->rewardPoolMap = $rewardPoolMap;
        $this->inJinliRankGiftValue = $inJinliRankGiftValue;

        Log::info(sprintf('Turntable::decodeFromJson ok turntableId=%d inJinliRankGiftValue=%d', $this->turntableId, $this->inJinliRankGiftValue));
    }
}