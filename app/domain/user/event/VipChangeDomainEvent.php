<?php


namespace app\domain\user\event;


class VipChangeDomainEvent
{
    public $vip = null;

    public function __construct($vip) {
        $this->vip = vip;
    }
}