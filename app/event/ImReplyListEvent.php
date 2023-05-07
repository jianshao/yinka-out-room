<?php

namespace app\event;


//im查看评论列表事件
class ImReplyListEvent extends AppEvent
{
    public $userId = 0;//用户id

    public function __construct($userId, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
    }
}