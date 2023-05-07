<?php

namespace app\event;


//点赞
class EnjoyForumEvent extends AppEvent
{
    public $userId = 0;
    public $forumUid = 0;
    public $type = 0; //点赞 type 1点赞 2取消赞

    public function __construct($userId,$forumUid, $type, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->forumUid = $forumUid;
        $this->type = $type;
    }
}