<?php

namespace app\event;

//回复帖子 type 1回帖2评论
class ReplyForumEvent extends AppEvent
{

    public $replyAtuid = 0;
    public $forumUid = 0;

    public function __construct($forumUid, $replyAtuid, $timestamp)
    {
        parent::__construct($timestamp);
        $this->forumUid = $forumUid;
        $this->replyAtuid = $replyAtuid;
    }

}
