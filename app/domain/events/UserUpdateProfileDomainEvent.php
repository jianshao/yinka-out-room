<?php

namespace app\domain\events;

use think\Log;

//完善资料
class UserUpdateProfileDomainEvent extends DomainUserEvent
{
    public function __construct($user, $timestamp) {
        parent::__construct($user, $timestamp);
    }
}