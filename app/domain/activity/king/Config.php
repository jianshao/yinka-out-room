<?php


namespace app\domain\activity\king;


class Config
{
    public static $CONF = '
        {
            "startTime":"2021-09-09 00:00:00",
            "stopTime":"2021-09-15 23:59:59",
            "welfare1_reward":{
                "giftId":466,
                "count":1
            },
            "welfare2_reward":{
                "giftId":467,
                "count":1
            }
        }
    ';

    public static function loadConf() {
        return json_decode(self::$CONF, true);
    }
}