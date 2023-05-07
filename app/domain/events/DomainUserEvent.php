<?php

namespace app\domain\events;

class DomainUserEvent extends DomainEvent
{
    public $user = null;
    public function __construct($user, $timestamp) {
        parent::__construct($timestamp);
        $this->user = $user;
    }
}