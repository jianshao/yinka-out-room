<?php


namespace app\domain\level\model;


class LevelModel
{
    public $level = 0;
    // level经验值
    public $levelExp = 0;
    //vip等级 跟加速有关 //svip消费1.05倍等级加速
    public $vipLevel = 0;

    public function __construct($level=0, $levelExp=0,$vipLevel=0) {
        $this->level = $level;
        $this->levelExp = $levelExp;
        $this->vipLevel = $vipLevel;
    }
}