<?php


namespace app\domain\game\box2;


use app\utils\ArrayUtil;

class RunningRewardPool
{
    public $boxId = 0;
    public $poolId = 0;
    // map<giftId, weight>
    public $giftMap = [];

    public function __construct($boxId, $poolId) {
        $this->boxId = $boxId;
        $this->poolId = $poolId;
    }

    /**
     * 从奖池里拿指定礼物
     *
     * @param $reGiftList
     * @return array|null[]
     */
    private function pickGift($reGiftList) {
        foreach ($reGiftList as $reGift) {
            $giftCount = ArrayUtil::safeGet($this->giftMap, $reGift->giftId, 0);
            if ($giftCount > 0) {
                $this->giftMap[$reGift->giftId] -= 1;
                return [$reGift->giftId, $reGift];
            }
        }
        return [null, null];
    }

    /**
     * 奖池里随机拿
     *
     * @return mixed|null
     * @throws \Exception
     */
    private function randomGiftImpl() {
        $total = 0;
        $giftIdWeights = [];
        foreach ($this->giftMap as $giftId => $weight) {
            if ($weight > 0) {
                $total += $weight;
                $giftIdWeights[] = [$giftId, $weight, $total];
            }
        }
        if ($total > 0) {
            $r = random_int(1, $total);
            foreach ($giftIdWeights as list($giftId, $weight, $weightLimit)) {
                if ($r <= $weightLimit) {
                    $this->giftMap[$giftId] -= 1;
                    return $giftId;
                }
            }
        }
        return null;
    }

    /**
     * 从奖池中拿出礼物
     *
     * @param $reGiftMap
     */
    public function randomGift($reGiftList) {
        // 优先拿reGiftMap里的礼物
        $giftId = null;
        $reGift = null;
        if (!empty($reGiftList)) {
            list($giftId, $reGift) = $this->pickGift($reGiftList);
        }
        if ($giftId != null) {
            return [$giftId, $reGift];
        }
        return [$this->randomGiftImpl(), null];
    }
}