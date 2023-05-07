<?php


namespace app\domain\activity\giftReturn;


class Config
{
    public static $CONF = '
        {
            "startTime":"2021-09-01 00:00:00",
            "stopTime":"2021-09-03 23:59:59",
            "rate":0.02,
            "giftIds":[290,292,437,367,236,384,385,234,363,336,369,241,267,296,386,383]
        }
    ';

    public static function loadConf() {
        return json_decode(self::$CONF, true);
    }
}