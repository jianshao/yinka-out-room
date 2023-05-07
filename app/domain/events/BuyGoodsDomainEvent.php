<?php

namespace app\domain\events;

use think\Log;


//购买商品
class BuyGoodsDomainEvent extends DomainUserEvent
{
    public $user = 0;
    //消耗的资产
    public $consumeAsset = null;
    //增加的资产
    public $addAsset = null;
    //购买的数量
    public $count = 0;

    public $mallId = '';
    public $from = '';

    public function __construct($user, $consumeAsset, $addAsset, $count, $mallId, $from, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->consumeAsset = $consumeAsset;
        $this->addAsset = $addAsset;
        $this->count = $count;
        $this->mallId = $mallId;
        $this->from = $from;
    }
}