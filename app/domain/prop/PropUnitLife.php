<?php

namespace app\domain\prop;

/**
 * 永不过期
 */
class PropUnitLife extends PropUnit
{
    public static $TYPE_NAME = 'life';

    public function add($prop, $count, $timestamp) {
        $prop->expiresTime = 0;
        return 1;
    }

    public function consume($prop, $count, $timestamp) {
        $prop->expiresTime = 0;
        return 1;
    }

    public function balance($prop, $timestamp) {
        return 1;
    }

    public function translateOld($prop, $timestamp) {
        $prop->expiresTime = 0;
        $prop->count = 1;
    }

    public function balanceByPropModel($propModel, $timestamp) {
        return 1;
    }

    public function isTiming() {
        return true;
    }

    public function buildDisplay($prop, $timestamp) {
        return '永久';
    }
}


