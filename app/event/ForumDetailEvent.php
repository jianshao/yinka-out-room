<?php

namespace app\event;


//动态详情
class ForumDetailEvent extends AppEvent
{
    public $forumUid = 0;//动态发帖人id
    public $userId = 0;//用户id

    public function __construct($forumUid, $userId, $timestamp)
    {
        parent::__construct($timestamp);
        $this->forumUid = $forumUid;
        $this->userId = $userId;
    }
}