<?php


namespace app\event;


class UserGreetEvent extends AppEvent
{
    public $userId = 0; //打招呼
    public $greetUserId = 0; //被打招呼

    public function __construct($userId, $greetUserId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->greetUserId = $greetUserId;
    }
}