<?php

namespace app\domain\events;

use think\Log;


class CompleteRealUserDomainEvent extends DomainUserEvent
{
    public function __construct($user, $timestamp) {
        parent::__construct($user, $timestamp);
    }
}