<?php


namespace app\domain\events;

//关注一位好友
class FocusFriendDomainEvent extends DomainUserEvent
{
    public $userId = 0;
    public $friendId = 0;
    public $isFocus = 0;//1 加关 2取关

    public function __construct($user, $friendId, $isFocus, $timestamp)
    {
        parent::__construct($user, $timestamp);
        $this->friendId = $friendId;
        $this->isFocus = $isFocus;
    }
}