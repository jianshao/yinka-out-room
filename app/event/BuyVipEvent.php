<?php


namespace app\event;


class BuyVipEvent extends AppEvent
{
    // VIP变化通知
    public $userId = 0;
    public $orderId = '';
    public $vipLevel = 0;
    public $count = 0;
    public $expiresTime = 0;
    public $isOpen = true;

    public function __construct($userId, $orderId, $vipLevel, $count, $expiresTime, $isOpen, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->orderId = $orderId;
        $this->vipLevel = $vipLevel;
        $this->count = $count;
        $this->expiresTime = $expiresTime;
        $this->isOpen = $isOpen;
    }
}