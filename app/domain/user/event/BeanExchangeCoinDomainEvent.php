<?php


namespace app\domain\user\event;


use app\domain\events\DomainUserEvent;

class BeanExchangeCoinDomainEvent extends DomainUserEvent
{
    public $beanCount = 0;
    public $coinCount = 0;
    public $beanBalance = 0;
    public $coinBalance = 0;

    public function __construct($user, $beanBalance, $beanCount, $coinBalance, $coinCount, $timestamp)
    {
        parent::__construct($user, $timestamp);
        $this->beanBalance = $beanBalance;
        $this->beanCount = $beanCount;
        $this->coinBalance = $coinBalance;
        $this->coinCount = $coinCount;
    }
}