<?php


namespace app\event;


class VipWillExpiresEvent extends AppEvent
{
    public $userId = 0;
    public $vipLevel = 0;
    public $vipExpiresTime = 0;
    public $nDay = 0;

    public function __construct($userId, $vipLevel, $vipExpiresTime, $nDay, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->vipLevel = $vipLevel;
        $this->vipExpiresTime = $vipExpiresTime;
        $this->nDay = $nDay;
    }
}