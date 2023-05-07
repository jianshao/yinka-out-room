<?php


namespace app\domain\vip\model;


class VipModel
{
    public $level = 0;
    public $vipExpiresTime = 0;
    public $svipExpiresTime = 0;

    public function __construct($level=0, $vipExpiresTime=0, $svipExpiresTime=0) {
        $this->level = $level;
        $this->vipExpiresTime = $vipExpiresTime;
        $this->svipExpiresTime = $svipExpiresTime;
    }

    public function copyTo($other) {
        $other->level = $this->level;
        $other->vipExpiresTime = $this->vipExpiresTime;
        $other->svipExpiresTime = $this->svipExpiresTime;
        return $other;
    }
}