<?php


namespace app\event;


class VipExpiresEvent extends AppEvent
{
    public $userId = 0;
    public $vipLevel = 0;

    public function __construct($userId, $vipLevel, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->vipLevel = $vipLevel;
    }
}