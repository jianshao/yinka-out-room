<?php

namespace app\event;


//购买商品
class IosChargeEvent extends AppEvent
{
    public $userId = 0;

    public function __construct($userId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
    }
}