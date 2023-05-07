<?php


namespace app\domain\pay\model;


class UserChargeStaticsModel
{
    public $chargeAmount = 0;
    public $chargeTimes = 0;

    public function __construct($chargeAmount=0, $chargeTimes=0) {
        $this->chargeAmount = $chargeAmount;
        $this->chargeTimes = $chargeTimes;
    }
}