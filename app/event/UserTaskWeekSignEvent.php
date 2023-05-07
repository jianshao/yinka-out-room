<?php


namespace app\event;


class UserTaskWeekSignEvent extends AppEvent
{
    public $userId = 0;

    public function __construct($userId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
    }
}