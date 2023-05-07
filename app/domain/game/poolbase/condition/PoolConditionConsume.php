<?php


namespace app\domain\game\poolbase\condition;


use think\facade\Log;

/**
 * 消耗条件
 *
 * Class PoolConditionConsume
 * @package app\domain\game\turntable\conditions
 */
class PoolConditionConsume extends PoolCondition
{
    public static $TYPE_ID = 'consume';
    public $consumeRange = [];

    public function __construct($consumeRange=[]) {
        $this->consumeRange = $consumeRange;
    }

    public function decodeFromJson($jsonObj) {
        if (array_key_exists('range', $jsonObj)) {
            $consumeRange = $jsonObj['range'];
            $this->consumeRange[0] = min($consumeRange[0], $consumeRange[1]);
            $this->consumeRange[1] = max($consumeRange[0], $consumeRange[1]);
        }
        return $this;
    }

    public function checkCondition($rewardPool, $boxUser) {
        if (!empty($this->consumeRange)) {
            $poolStatics = $boxUser->findPoolStatics($rewardPool->poolId);
            $consume = $poolStatics != null ? $poolStatics->curStaticsData->consume : 0;
            $ret = (($this->consumeRange[0] < 0 || $consume >= $this->consumeRange[0])
                && ($this->consumeRange[1] < 0 || $consume < $this->consumeRange[1]));

            Log::debug(sprintf('PoolConditionConsume::checkCondition userId=%d poolId=%d consumeRange=[%d:%d] consume=%d ret=%d',
                $boxUser->userId, $rewardPool->poolId, $this->consumeRange[0], $this->consumeRange[1], $consume, $ret));
            return $ret;
        }
        return true;
    }
}