<?php


namespace app\domain\activity\guoqing;


class Config
{
    public static $CONF = '
        {
            "startTime":"2021-10-01 00:00:00",
            "stopTime":"2021-10-07 23:59:59",
            "rate":0.02,
            "basePool":999,
            "poolRates":[0.3,0.2,0.15,0.08,0.07,0.06,0.05,0.04,0.03,0.02],
            "giftIds":[471, 472, 473],
            "forumReward":{
                "assetId":"prop:232",
                "count":1
            },
            "boxs":[
                {
                    "id": 1,
                    "name": "莫高窟",
                    "energy": 5200,
                    "rewards":[
                        {
                            "assetId":"gift:408",
                            "count":1
                        },
                        {
                            "assetId":"prop:233",
                            "count":3
                        }                 
                    ]
                },
                {
                    "id": 2,
                    "name": "兵马俑",
                    "energy": 9999,
                    "rewards":[
                        {
                            "assetId":"gift:426",
                            "count":1
                        },
                        {
                            "assetId":"prop:234",
                            "count":3
                        }                 
                    ]
                },
                {
                    "id": 3,
                    "name": "九寨沟",
                    "energy": 13140,
                    "rewards":[
                        {
                            "assetId":"gift:429",
                            "count":1
                        },
                        {
                            "assetId":"prop:235",
                            "count":3
                        }                 
                    ]
                },
                {
                    "id": 4,
                    "name": "象鼻山",
                    "energy": 33440,
                    "rewards":[
                        {
                            "assetId":"gift:448",
                            "count":1
                        },
                        {
                            "assetId":"prop:236",
                            "count":3
                        }                 
                    ]
                },
                {
                    "id": 5,
                    "name": "张家界",
                    "energy": 52000,
                    "rewards":[
                        {
                            "assetId":"gift:425",
                            "count":1
                        },
                        {
                            "assetId":"prop:237",
                            "count":3
                        }                 
                    ]
                },
                {
                    "id": 6,
                    "name": "凤凰岛",
                    "energy": 99999,
                    "rewards":[
                        {
                            "assetId":"gift:437",
                            "count":1
                        },
                        {
                            "assetId":"prop:238",
                            "count":3
                        }                 
                    ]
                },
                {
                    "id": 7,
                    "name": "天安门",
                    "energy": 131400,
                    "rewards":[
                        {
                            "assetId":"gift:454",
                            "count":1
                        },
                        {
                            "assetId":"prop:239",
                            "count":3
                        }                 
                    ]
                }
            ]
        }
    ';

    public static function loadConf() {
        return json_decode(self::$CONF, true);
    }
}