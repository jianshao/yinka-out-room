<?php


namespace app\domain\game\turntable;


use app\domain\gift\GiftUtils;

/**
 * 奖池统计，包含总统计和当前统计
 *
 * Class UserPoolStatics
 * @package app\domain\game\turntable
 */
class PoolStatics
{
    // 池子ID
    public $poolId = 0;
    // 总统计
    public $totalStaticsData = null;
    // 当前统计
    public $curStaticsData = null;

    public function __construct($poolId) {
        $this->poolId = $poolId;
        $this->totalStaticsData = new PoolStaticsData();
        $this->curStaticsData = new PoolStaticsData();
    }

    /**
     * 增加消耗和奖励
     *
     * @param $consume
     * @param $rewardValue
     * @param $giftMap
     * @param $timestamp
     */
    public function add($consume, $rewardValue) {
        $this->totalStaticsData->add($consume, $rewardValue);
        $this->curStaticsData->add($consume, $rewardValue);
        return $this;
    }

    /**
     * 清除当前奖池统计
     *
     */
    public function clearCurStatics() {
        $this->curStaticsData->clear();
    }

    /**
     * json解析
     *
     * @param $jsonObj
     * @return $this
     */
    public function fromJson($jsonObj) {
        $this->totalStaticsData->fromJson($jsonObj['total']);
        $this->curStaticsData->fromJson($jsonObj['cur']);
        return $this;
    }

    /**
     * encode成json
     * @return array
     */
    public function toJson() {
        return [
            'total' => $this->totalStaticsData->toJson(),
            'cur' => $this->curStaticsData->toJson()
        ];
    }

    public function toJsonWithBaolv() {
        return [
            'total' => $this->totalStaticsData->toJsonWithBaolv(),
            'cur' => $this->curStaticsData->toJsonWithBaolv()
        ];
    }
}