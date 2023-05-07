<?php


namespace app\domain\game\turntable;


/**
 * 奖池统计
 *
 * Class PoolStatics
 * @package app\domain\game\turntable
 */
class PoolStaticsData
{
    // 总消耗豆数量
    public $consume = 0;
    // 产出总价值
    public $rewardValue = 0;

    /**
     * 增加消耗和奖励
     *
     * @param $consume
     * @param $giftMap
     */
    public function add($consume, $rewardValue) {
        $this->consume += $consume;
        $this->rewardValue += $rewardValue;
        return $this;
    }

    public function clear() {
        $this->consume = 0;
        $this->rewardValue = 0;
        return $this;
    }

    public function getBaolv() {
        if ($this->consume != 0) {
            return (float)$this->rewardValue / (float)$this->consume;
        }
        return 0;
    }

    public function fromJson($jsonObj) {
        $this->consume = $jsonObj['consume'];
        $this->rewardValue = $jsonObj['rewardValue'];
        return $this;
    }

    public function toJson() {
        return [
            'consume' => $this->consume,
            'rewardValue' => $this->rewardValue
        ];
    }

    public function toJsonWithBaolv() {
        return [
            'consume' => $this->consume,
            'rewardValue' => $this->rewardValue,
            'baolv' => $this->consume != 0 ? round(floatval($this->rewardValue) / floatval($this->consume), 6) : 0
        ];
    }
}