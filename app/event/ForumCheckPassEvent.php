<?php


namespace app\event;


class ForumCheckPassEvent extends AppEvent
{
    // 动态审核通过
    public $userId = 0;
    public $forumId = 0; #动态id
    public $topicId = 0; #话题id

    public function __construct($userId, $forumId, $topicId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->forumId = $forumId;
        $this->topicId = $topicId;
    }
}