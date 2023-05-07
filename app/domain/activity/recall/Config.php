<?php


namespace app\domain\activity\recall;


class Config
{
    public static $CONF = '
        {
            "startTime":"2021-01-05 00:00:00",
            "stopTime":"2021-01-06 00:00:00",
            "days":[
                {
                    "dayRange":[5, -1],
                    "items":[
                        {
                            "rmbRange":[0, 1],
                            "rewards":[
                                {
                                    "assetId":"prop:111",
                                    "count":7,
                                    "name":"七日回归头像框*7天",
                                    "img":"Public/Uploads/image/logo.png"
                                }
                            ]
                        },
                        {
                            "rmbRange":[1, 500],
                            "rewards":[
                                {
                                    "assetId":"prop:111",
                                    "count":7,
                                    "name":"七日回归头像框*7天",
                                    "img":"Public/Uploads/image/logo.png"
                                },
                                {
                                    "assetId":"user:vip",
                                    "count":5,
                                    "name":"VIP特权*5天",
                                    "img":"Public/Uploads/image/logo.png"
                                }
                            ]
                        },
                        {
                            "rmbRange":[500, 5000],
                            "rewards":[
                                {
                                    "assetId":"prop:111",
                                    "count":7,
                                    "name":"七日回归头像框*7天",
                                    "img":"Public/Uploads/image/logo.png"
                                },
                                {
                                    "assetId":"user:vip",
                                    "count":7,
                                    "name":"VIP特权*7天",
                                    "img":"Public/Uploads/image/logo.png"
                                }
                            ]
                        },
                        {
                            "rmbRange":[5000, -1],
                            "rewards":[
                                {
                                    "assetId":"prop:111",
                                    "count":7,
                                    "name":"七日回归头像框*7天",
                                    "img":"Public/Uploads/image/logo.png"
                                },
                                {
                                    "assetId":"user:svip",
                                    "count":15,
                                    "name":"VIP特权*15天",
                                    "img":"Public/Uploads/image/logo.png"
                                },
                                {
                                    "assetId":"gift:372",
                                    "count":1,
                                    "name":"特殊礼物*1个",
                                    "img":"Public/Uploads/image/logo.png"
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    ';

    public static $SMSCONF = '
        {
            "loginStartTime": "2021-11-27 00:00:00",
            "startTime": "2021-12-04 00:00:00",
            "stopTime": "2021-12-12 24:00:00",
            "displayName": "用户召回短信活动",
            "senderAssets": [{
                "assetId": "prop:254",
                "count": 7 ,
                "name": "达人回归头像框*7天",
                "img": "Public/Uploads/image/logo.png"
            }]
        }
    ';


    public static function loadRecallConf()
    {
        return json_decode(self::$CONF, true);
    }


    public static function loadRecallSmsConf()
    {
        return json_decode(self::$SMSCONF, true);
    }
}