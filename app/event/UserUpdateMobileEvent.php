<?php


namespace app\event;


class UserUpdateMobileEvent extends AppEvent
{
    public $userId = 0;
    public $mobile = 0;

    public function __construct($userId, $mobile, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->mobile = $mobile;
    }
}