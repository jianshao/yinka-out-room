<?php


namespace app\domain\game\box2;


use app\domain\exceptions\FQException;
use app\domain\game\box2\condition\PoolConditionAnd;
use app\domain\game\box2\condition\PoolConditionBaolv;
use app\domain\game\box2\condition\PoolConditionConsume;
use app\domain\game\box2\condition\PoolConditionOr;
use app\domain\gift\GiftSystem;
use app\utils\ArrayUtil;
use think\facade\Log;

class RewardPool
{
    // 奖池ID
    public $poolId = 0;
    // 奖池类型
    public $poolType = 0;
    // 奖池名称
    public $poolName = '';
    // 礼物权重配置
    // map<giftId, weight>
    public $giftMap = [];
    public $totalWeight = 0;
    // 入池条件
    public $condition = null;
    // 排序值
    public $sort = 0;
    public $typedPool = null;

    public function fromJson($jsonObj) {
        $this->poolId = $jsonObj['poolId'];
        $this->poolType = $jsonObj['type'];
        $this->sort = $jsonObj['sort'];

        $this->totalWeight = 0;
        foreach ($jsonObj['gifts'] as $giftWeight) {
            $giftId = $giftWeight[0];
            $weight = $giftWeight[1];
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind == null) {
                Log::error(sprintf('RewardPool::fromJson UnknownGiftId poolId=%d giftId=%d',
                    $this->poolId, $giftId));
                throw new FQException('礼物配置错误：'.$giftId, 500);
            }
            $this->totalWeight += $weight;
            $this->giftMap[$giftId] = $weight;
        }

        $conditionJsonList = ArrayUtil::safeGet($jsonObj, 'condition');
        if ($conditionJsonList != null) {
            $conditions = [];
            foreach ($conditionJsonList as $conditionJson) {
                $andConditions = [];
                $consumeRange = ArrayUtil::safeGet($conditionJson, 'consume');
                if ($consumeRange != null) {
                    if (count($consumeRange) != 2 || $consumeRange[1] < $consumeRange[0]) {
                        Log::error(sprintf('RewardPool::fromJson BadConditionConsume poolId=%d condition=%s',
                            $this->poolId, json_encode($conditionJson)));
                        throw new FQException('条件配置错误', 500);
                    }
                    $andConditions[] = new PoolConditionConsume($consumeRange);
                }
                $baolvRange = ArrayUtil::safeGet($conditionJson, 'baolv');
                if ($baolvRange != null) {
                    if (count($baolvRange) != 2 || $baolvRange[1] < $baolvRange[0]) {
                        Log::error(sprintf('RewardPool::fromJson BadConditionBaolv poolId=%d condition=%s',
                            $this->poolId, json_encode($conditionJson)));
                        throw new FQException('爆率配置错误', 500);
                    }
                    $andConditions[] = new PoolConditionBaolv($baolvRange);
                }
                if ($andConditions) {
                    $conditions[] = new PoolConditionAnd($andConditions);
                }
            }
            if (!empty($conditions)) {
                $this->condition = new PoolConditionOr($conditions);
            }
        }

        return $this;
    }
}