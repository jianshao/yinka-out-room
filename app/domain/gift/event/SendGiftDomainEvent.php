<?php

namespace app\domain\gift\event;

use app\domain\events\DomainUserEvent;

class SendGiftDomainEvent extends DomainUserEvent
{
    public $roomId = 0;
    public $receiveUser = null;
    public $giftDetailsList = null;
    public $fromBag = false;

    public function __construct($roomId, $user, $receiveUser, $giftDetailsList, $fromBag, $timestamp) {
        parent::__construct($user, $timestamp);
        $this->roomId = $roomId;
        $this->receiveUser = $receiveUser;
        $this->giftDetailsList = $giftDetailsList;
        $this->fromBag = $fromBag;
    }
}