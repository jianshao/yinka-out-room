<?php

namespace app\event;


//我的-关注列表
class CareUserListEvent extends AppEvent
{
    public $userId = 0;  //用户id
    public $type = 0;  //1关注列表 2粉丝列表 3好友 4黑名单

    public function __construct($userId, $type, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->type = $type;
    }
}