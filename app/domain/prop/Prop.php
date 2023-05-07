<?php

namespace app\domain\prop;

use app\utils\TimeUtil;
use think\facade\Log;

/**
 * 道具实例
 */
abstract class Prop
{
    // 道具种类
    public $kind = null;
    // 道具ID
    public $propId = 0;
    // 创建时间
    public $createTime = 0;
    // 修改时间
    public $updateTime = 0;
    // 到期时间 <=0为永不过期
    public $expiresTime = 0;
    // 剩余数量
    public $count = 0;
    // 是否穿戴 0 未穿戴 1 已穿戴
    public $isWore = 0;
    // 佩戴/取消佩戴时间时间
    public $woreTime = 0;

    public function __construct($propKind, $propId) {
        $this->kind = $propKind;
        $this->propId = $propId;
    }

    public function add($count, $timestamp) {
        return $this->kind->unit->add($this, $count, $timestamp);
    }

    public function consume($count, $timestamp) {
        return $this->kind->unit->consume($this, $count, $timestamp);
    }

    public function balance($timestamp) {
        return $this->kind->unit->balance($this, $timestamp);
    }

    public function breakUpBalance($timestamp) {
        return $this->kind->unit->breakUpBalance($this, $timestamp);
    }

    public function isDied($timestamp) {
        return $this->balance($timestamp) <= 0;
    }

    public function isTiming() {
        return $this->kind->unit->isTiming();
    }

    public function initByPropModel($propModel) {
        $this->createTime = $propModel->createTime;
        $this->updateTime = $propModel->updateTime;
        $this->expiresTime = $propModel->expiresTime;
        $this->count = $propModel->count;
        $this->isWore = $propModel->isWore;
        $this->woreTime = $propModel->woreTime;
        $this->initByPropModelImpl($propModel);
    }

    protected function initByPropModelImpl($propModel) {
    }
}


