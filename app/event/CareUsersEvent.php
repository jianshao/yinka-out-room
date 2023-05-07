<?php

namespace app\event;


//关注用户
class CareUsersEvent extends AppEvent
{
    public $userId = 0;  //关注的用户id
    public $type = 0;  //1 加关 2取关

    public function __construct($userId, $type, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->type = $type;
    }
}