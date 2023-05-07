<?php


namespace app\domain\events;


class DomainEvent
{
    public $timestamp = 0;
    public function __construct($timestamp) {
        $this->timestamp = $timestamp;
    }
}