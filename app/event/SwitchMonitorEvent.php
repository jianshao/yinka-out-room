<?php


namespace app\event;


class SwitchMonitorEvent extends AppEvent
{
    public $userId = 0;
    public $switch = false;
    public function __construct($userId,$switch, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->switch = $switch;
    }
}