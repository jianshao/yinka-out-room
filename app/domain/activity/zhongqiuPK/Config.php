<?php


namespace app\domain\activity\zhongqiuPK;


class Config
{
    public static $CONF = '
        {
            "startTime":"2021-09-20 00:00:00",
            "stopTime":"2021-09-26 23:59:59",
            "rate":0.02,
            "basePool":9999,
            "poolRates":[0.3,0.2,0.15,0.08,0.07,0.06,0.05,0.04,0.03,0.02],
            "giftIds":[469, 470],
            "retroactive":100,
            "checkinReward":{
                "assetId":"gift:468",
                "count":1
            },
            "checkins":[
                {
                    "assetId":"prop:227",
                    "count":1
                },
                {
                    "assetId":"prop:228",
                    "count":1
                },
                {
                    "assetId":"prop:227",
                    "count":2
                },
                {
                    "assetId":"prop:228",
                    "count":2
                },
                {
                    "assetId":"prop:229",
                    "count":1
                },
                {
                    "assetId":"prop:230",
                    "count":1
                },
                {
                    "assetId":"prop:231",
                    "count":1
                }
            ]
        }
    ';

    public static function loadConf() {
        return json_decode(self::$CONF, true);
    }
}