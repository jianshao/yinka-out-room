<?php


namespace app\domain\game\box2;


use app\utils\ArrayUtil;
use think\facade\Log;

class Box2User
{
    // 用户ID
    public $userId = 0;
    // 宝箱ID
    public $boxId = 0;
    // 当前奖池类型
    public $curPoolType = '';
    // 当前奖池ID
    public $curPoolId = 0;
    // 该宝箱所有奖池统计 map<poolId, PoolStatics>
    public $poolStaticsMap = [];

    public function __construct($userId, $boxId) {
        $this->userId = $userId;
        $this->boxId = $boxId;
    }

    public function findPoolStatics($poolId) {
        return ArrayUtil::safeGet($this->poolStaticsMap, $poolId);
    }

    public function entryPool($poolType, $poolId) {
        if ($this->curPoolType != $poolType || $this->curPoolId != $poolId) {
            if ($this->curPoolId != 0) {
                Log::info(sprintf('Box2User::entryPool changePool userId=%d old=%s:%d new=%s:%d',
                    $this->userId, $this->curPoolType, $this->curPoolId, $poolType, $poolId));
                $this->clearPoolStatics($this->curPoolId);
            }
            $this->clearPoolStatics($poolId);
            $this->curPoolType = $poolType;
            $this->curPoolId = $poolId;
        }
    }

    public function clearPoolStatics($poolId) {
        $poolStatics = $this->findPoolStatics($poolId);
        if ($poolStatics != null) {
            $poolStatics->curStaticsData->clear();
        }
        Log::info(sprintf('Box2User::clearPoolStatics userId=%d boxId=%d poolId=%d', $this->userId, $this->boxId, $poolId));
    }

    public function add($poolId, $consume, $rewardValue) {
        if (!array_key_exists($poolId, $this->poolStaticsMap)) {
            $this->poolStaticsMap[$poolId] = new PoolStatics($poolId);
        }
        $this->poolStaticsMap[$poolId]->add($consume, $rewardValue);
    }

    public function fromJson($jsonObj) {
        $this->curPoolType = ArrayUtil::safeGet($jsonObj, 'curPoolType', '');
        $this->curPoolId = ArrayUtil::safeGet($jsonObj, 'curPoolId', 0);
        $poolStaticsMapJson = ArrayUtil::safeGet($jsonObj, 'pools');
        if (!empty($poolStaticsMapJson)) {
            foreach ($poolStaticsMapJson as $poolId => $poolStaticsJson) {
                $poolStatics = new PoolStatics($poolId);
                $poolStatics->fromJson($poolStaticsJson);
                $this->poolStaticsMap[$poolId] = $poolStatics;
            }
        }
        return $this;
    }

    public function toJson() {
        $pools = [];
        foreach ($this->poolStaticsMap as $poolId => $poolStatics) {
            $pools[$poolId] = $poolStatics->toJson();
        }

        return [
            'curPoolType' => $this->curPoolType,
            'curPoolId' => $this->curPoolId,
            'pools' => $pools
        ];
    }

    public function toJsonWithBaolv() {
        $pools = [];
        foreach ($this->poolStaticsMap as $poolId => $poolStatics) {
            $pools[$poolId] = $poolStatics->toJsonWithBaolv();
        }

        return [
            'curPoolType' => $this->curPoolType,
            'curPoolId' => $this->curPoolId,
            'pools' => $pools
        ];
    }
}