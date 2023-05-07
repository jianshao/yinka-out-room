<?php


namespace app\domain\game\box2;

class SpecialConf
{
    // 特殊奖励
    public $giftIds = [];
    // 最大进度
    public $maxProgress = 0;
    // 最大奖池额度
    public $maxPoolValue = 0;
    // 指定礼物奖池额度，指定礼物发出去之后当前奖池额度要减去这个值
    public $giftValue = 0;
    // 指定礼物爆奖概率最大值
    public $giftWeight = 0;

    public function fromJson($jsonObj) {
        $this->giftIds = $jsonObj['gifts'];
        $this->maxProgress = $jsonObj['maxProgress'];
        $this->maxPoolValue = $jsonObj['maxPoolValue'];
        $this->giftValue = $jsonObj['giftValue'];
        $this->giftWeight = $jsonObj['giftWeight'];
        return $this;
    }
}