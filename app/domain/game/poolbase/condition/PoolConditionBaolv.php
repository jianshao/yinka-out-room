<?php


namespace app\domain\game\poolbase\condition;

use think\facade\Log;

class PoolConditionBaolv extends PoolCondition
{
    public static $TYPE_ID = 'baolv';

    public $baolvRange = [];

    public function __construct($baolvRange=[]) {
        $this->baolvRange = $baolvRange;
    }

    public function decodeFromJson($jsonObj) {
        if (array_key_exists('range', $jsonObj)) {
            $baolvRange = $jsonObj['range'];
            $this->baolvRange[0] = min($baolvRange[0], $baolvRange[1]);
            $this->baolvRange[1] = max($baolvRange[0], $baolvRange[1]);
        }
        return $this;
    }

    public function checkCondition($rewardPool, $boxUser) {
        if (!empty($this->baolvRange)) {
            $userPoolStatics = $boxUser->findPoolStatics($rewardPool->poolId);
            $baolv = $userPoolStatics != null ? $userPoolStatics->curStaticsData->getBaolv() : 0;
            $ret = (($this->baolvRange[0] < 0 || $baolv >= $this->baolvRange[0])
                && ($this->baolvRange[1] < 0 || $baolv < $this->baolvRange[1]));
            Log::debug(sprintf('PoolConditionBaolv::checkCondition userId=%d poolId=%d baolvRange=[%.4f:%.4f] baolv=%.4f ret=%d',
                $boxUser->userId, $rewardPool->poolId, $this->baolvRange[0], $this->baolvRange[1], $baolv, $ret));
            return $ret;
        }
        return true;
    }
}