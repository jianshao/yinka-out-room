<?php

namespace app\event;

class DoLotteryEvent extends AppEvent
{
    public $userId = 0;
    public $totalPrice = 0;
    public $balance = 0;

    public function __construct($userId,$totalPrice,$balance,$timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->totalPrice = $totalPrice;
        $this->balance = $balance;
    }
}