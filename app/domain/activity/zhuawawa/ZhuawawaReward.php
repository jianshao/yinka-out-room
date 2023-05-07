<?php


namespace app\domain\activity\zhuawawa;


use app\domain\exceptions\FQException;
use app\domain\game\poolbase\condition\PoolConditionAnd;
use app\domain\game\poolbase\condition\PoolConditionBaolv;
use app\domain\game\poolbase\condition\PoolConditionConsume;
use app\domain\game\poolbase\condition\PoolConditionOr;
use app\domain\gift\GiftSystem;
use app\utils\ArrayUtil;
use think\facade\Log;

class ZhuawawaReward
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

    public function __construct($boxId=0, $poolId=0) {
        $this->boxId = $boxId;
        $this->poolId = $poolId;
    }

    public function fromJson($jsonObj) {
        $this->poolId = $jsonObj['poolId'];
        $this->poolType = $jsonObj['type'];
        $this->sort = $jsonObj['sort'];

        $this->totalWeight = 0;
        foreach ($jsonObj['gifts'] as $giftWeight) {
            $giftId = $giftWeight[0];
            $weight = $giftWeight[1];
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
                        throw new FQException('配置错误', 500);
                    }
                    $andConditions[] = new PoolConditionConsume($consumeRange);
                }
                $baolvRange = ArrayUtil::safeGet($conditionJson, 'baolv');
                if ($baolvRange != null) {
                    if (count($baolvRange) != 2 || $baolvRange[1] < $baolvRange[0]) {
                        Log::error(sprintf('RewardPool::fromJson BadConditionBaolv poolId=%d condition=%s',
                            $this->poolId, json_encode($conditionJson)));
                        throw new FQException('配置错误', 500);
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

    public function decodeFromRedisJson($jsonObj) {
        $this->giftMap = $jsonObj['gifts'];
        return $this;
    }

    public function encodeToDaoRedisJson() {
        $ret = [
            'gifts' => $this->giftMap,
        ];
        return json_encode($ret);
    }
}