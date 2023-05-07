<?php

namespace app\domain\events;

use think\Log;

class BindMobileDomainEvent extends DomainUserEvent
{
    public function __construct($user, $timestamp) {
        parent::__construct($user, $timestamp);
    }
}