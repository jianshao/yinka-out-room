<?php


namespace app\event;

//发布动态
class ReleaseDynamicEvent extends AppEvent
{
    public $userId = 0;
    public $forumId = 0;

    public function __construct($userId, $forumId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->forumId = $forumId;
    }
}