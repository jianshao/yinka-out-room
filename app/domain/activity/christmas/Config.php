<?php


namespace app\domain\activity\christmas;


class Config
{
    public static $CONF = '
        {
            "startTime":"2021-12-24 00:00:00",
            "stopTime":"2022-01-03 23:59:59",
            "exchangeConf":{"255":120, "256":120, "168":100, "167":100,"166":90, "165":90},
            "giftIds":[421,420,419,418,522,523,524,525,526,527,528,529]
        }
    ';

    public static function loadConf() {
        return json_decode(self::$CONF, true);
    }
}