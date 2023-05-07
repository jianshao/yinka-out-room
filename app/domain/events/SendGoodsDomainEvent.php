<?php

namespace app\domain\events;

use think\Log;


//购买商品
class SendGoodsDomainEvent extends DomainUserEvent
{
    public $user = 0;
    //消耗的资产
    public $consumeAsset = null;
    //接收人id
    public $receivedId = null;
    //购买的数量
    public $count = 0;

    public function __construct($user, $receivedId, $consumeAsset, $count, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->consumeAsset = $consumeAsset;
        $this->receivedId = $receivedId;
        $this->count = $count;
    }
}