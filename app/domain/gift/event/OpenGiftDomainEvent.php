<?php

namespace app\domain\gift\event;
use app\domain\events\DomainUserEvent;


//打开礼物
class OpenGiftDomainEvent extends DomainUserEvent
{
    public $roomId = 0;
    public $user = null;
    public $giftKind = null;
    public $count = 0;
    public $gainAssets = null;

    public function __construct($roomId, $user, $giftKind, $count, $gainAssets, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->user = $user;
        $this->roomId = $roomId;
        $this->giftKind = $giftKind;
        $this->count = $count;
        $this->gainAssets = $gainAssets;
    }
}