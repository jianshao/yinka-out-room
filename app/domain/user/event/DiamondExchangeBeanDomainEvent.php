<?php


namespace app\domain\user\event;


use app\domain\events\DomainUserEvent;

class DiamondExchangeBeanDomainEvent extends DomainUserEvent
{
    public $diamondCount = 0;
    public $beanBalance = 0;
    public $diamondBalance = 0;

    public function __construct($user, $diamondCount, $beanBalance, $diamondBalance , $timestamp) {
        parent::__construct($user, $timestamp);
        $this->diamondCount = $diamondCount;
        $this->beanBalance = $beanBalance;
        $this->diamondBalance = $diamondBalance;
    }
}