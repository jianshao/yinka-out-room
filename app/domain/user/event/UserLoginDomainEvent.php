<?php

namespace app\domain\user\event;

use app\domain\events\DomainUserEvent;

class UserLoginDomainEvent extends DomainUserEvent
{
    public function __construct($user, $timestamp) {
        parent::__construct($user, $timestamp);
    }
}