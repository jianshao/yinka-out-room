<?php


namespace app\domain\game\box2;


use app\domain\exceptions\FQException;
use think\facade\Log;

class TypedPool
{
    public $poolType = '';
    public $rewardPools = [];
    public $rewardPoolMap = [];

    public function __construct($poolType) {
        $this->poolType = $poolType;
    }

    public function indexOfRewardPool($poolId) {
        for ($i = 0; $i < count($this->rewardPools); $i++) {
            if ($this->rewardPools[$i]->poolId == $poolId) {
                return $i;
            }
        }
        return -1;
    }

    public function addRewardPool(&$rewardPool) {
        if (array_key_exists($rewardPool->poolId, $this->rewardPoolMap)) {
            Log::error(sprintf('TypedPool::addRewardPool poolAlreadyExists poolId=%s',
                $rewardPool->poolId));
            throw new FQException('奖池配置错误', 500);
        }
        $rewardPool->typedPool = $this;
        $this->rewardPoolMap[$rewardPool->poolId] = $rewardPool;
        $this->rewardPools[] = $rewardPool;
        return $this;
    }

    public function sortRewardPools() {
        usort($this->rewardPools, function($a, $b) {
            if ($a->sort < $b->sort) {
                return -1;
            } else if ($a->sort > $b->sort) {
                return 1;
            }
            return 0;
        });
    }
}