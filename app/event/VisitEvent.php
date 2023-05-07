<?php


namespace app\event;

//用户来访监听事件
class VisitEvent extends AppEvent
{
    public $userId = 0;

    public function __construct($userId,$timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
    }

}