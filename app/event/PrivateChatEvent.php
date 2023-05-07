<?php


namespace app\event;

//私聊一个用户
class PrivateChatEvent extends AppEvent
{
    public $userId = 0;
    public $otherUserId = 0;

    public function __construct($userId, $otherUserId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->otherUserId = $otherUserId;
    }
}