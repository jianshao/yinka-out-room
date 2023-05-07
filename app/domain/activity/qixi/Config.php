<?php


namespace app\domain\activity\qixi;


class Config
{
    public static $CONF = '
        {
            "startTime":"2021-08-13 00:00:00",
            "stopTime":"2021-08-20 00:00:00",
            "missingGifts":[462,463,464],
            "bags":[
                {
                    "giftId": 462,
                    "count":10,
                    "reward": {
                          "type": "RandomContent",
                          "randoms": [
                            {
                              "weight": 50,
                              "assetId": "gift:425",
                              "count": 1
                            },
                            {
                              "weight": 50,
                              "assetId": "user:bean",
                              "count": 777
                            }
                          ]
                        }
                },
                {
                    "giftId": 463,
                    "count":2,
                    "reward": {
                          "type": "RandomContent",
                          "randoms": [
                            {
                              "weight": 50,
                              "assetId": "gift:425",
                              "count": 2
                            },
                            {
                              "weight": 50,
                              "assetId": "user:bean",
                              "count": 1554
                            }
                          ]
                        }
                },
                {
                    "giftId": 464,
                    "count":1,
                    "reward": {
                          "type": "RandomContent",
                          "randoms": [
                            {
                              "weight": 50,
                              "assetId": "gift:425",
                              "count": 3
                            },
                            {
                              "weight": 50,
                              "assetId": "user:bean",
                              "count": 2331
                            }
                          ]
                        }
                }
            ]
        }
    ';

    public static function loadQiXiConf() {
        return json_decode(self::$CONF, true);
    }
}