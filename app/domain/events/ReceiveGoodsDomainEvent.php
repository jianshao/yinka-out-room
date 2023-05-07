<?php

namespace app\domain\events;

use think\Log;


//购买商品
class ReceiveGoodsDomainEvent extends DomainUserEvent
{
    public $user = 0;
    //赠送人id
    public $fromUserId = null;
    //增加的资产
    public $addAsset = null;
    //购买的数量
    public $count = 0;


    public function __construct($user, $fromUserId, $addAsset, $count, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->addAsset = $addAsset;
        $this->fromUserId = $fromUserId;
        $this->count = $count;
    }
}