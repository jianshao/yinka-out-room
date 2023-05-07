<?php

namespace app\event;


//打招呼列表
class CallListEvent extends AppEvent
{
    public $userId = 0;

    public function __construct($userId, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
    }
}