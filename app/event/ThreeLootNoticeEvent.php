<?php

namespace app\event;


//购买商品
class ThreeLootNoticeEvent extends AppEvent
{
    public $order = null;
    public $tableId = 0;
    //奖励
    public $rewards = null;

    public function __construct($order, $tableId, $timestamp) {
        parent::__construct($timestamp);
        $this->order = $order;
        $this->tableId = $tableId;
    }
}