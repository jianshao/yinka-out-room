<?php


namespace app\domain\game\box;


class Config
{
    public static $BOX_CONF = '{
        "eggCoin": 500,
        "fullServerCoin": 5000,
        "counts": [1, 10, 66],
        "boxes": [{
                "boxId": "silver",
                "hammerPropId": 201,
                "price": {
                    "assetId": "user:bean",
                    "count": 20
                },
                "avatarKindId": 1,
                "maxPersonalProgress": 1500,
                "maxGlobalProgress": 30000,
                "personalSpecialGifts": [372],
                "globalSpecialGifts": [372],
                "gifts": [{
                        "giftId": 384,
                        "weight": 1
                    },
                    {
                        "giftId": 396,
                        "weight": 1
                    },
                    {
                        "giftId": 367,
                        "weight": 6
                    },
                    {
                        "giftId": 369,
                        "weight": 18
                    },
                    {
                        "giftId": 336,
                        "weight": 788
                    },
                    {
                        "giftId": 362,
                        "weight": 1388
                    },
                    {
                        "giftId": 294,
                        "weight": 9688
                    },
                    {
                        "giftId": 250,
                        "weight": 35888
                    },
                    {
                        "giftId": 251,
                        "weight": 35888
                    }
                ]
            },
            {
                "boxId": "gold",
                "hammerPropId": 202,
                "price": {
                    "assetId": "user:bean",
                    "count": 100
                },
                "avatarKindId": 2,
                "maxPersonalProgress": 300,
                "maxGlobalProgress": 5000,
                "personalSpecialGifts": [371],
                "globalSpecialGifts": [371],
                "gifts": [{
                        "giftId": 403,
                        "weight": 1
                    },
                    {
                        "giftId": 299,
                        "weight": 1
                    },
                    {
                        "giftId": 242,
                        "weight": 1
                    },
                    {
                        "giftId": 384,
                        "weight": 3
                    },
                    {
                        "giftId": 236,
                        "weight": 88
                    },
                    {
                        "giftId": 370,
                        "weight": 1508
                    },
                    {
                        "giftId": 336,
                        "weight": 15888
                    },
                    {
                        "giftId": 343,
                        "weight": 25888
                    },
                    {
                        "giftId": 408,
                        "weight": 88888
                    }
                ]
            }
        ]
    }';

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function getBoxConf() {
        return json_decode(self::$BOX_CONF, true);
    }
}