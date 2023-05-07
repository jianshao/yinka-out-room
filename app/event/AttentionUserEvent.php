<?php

namespace app\event;


//关注用户
class AttentionUserEvent extends AppEvent
{
    public $userId = 0;
    public $attentionUserIds = [];

    public function __construct($userId, $attentionUserIds, $timestamp)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->attentionUserIds = $attentionUserIds;
    }
}