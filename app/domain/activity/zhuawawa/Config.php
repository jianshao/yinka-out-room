<?php


namespace app\domain\activity\zhuawawa;


class Config
{
    public static $CONF = '
        {
            "startTime": "2022-03-25 00:00:00",
            "stopTime": "2022-03-31 23:59:59",
            "props": [{
                "id": 1,
                "name": "空奖",
                "images": "",
                "desc": "空奖",
                "price": 0,
                "rate": "49.40",
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 0
                }
            }, {
                "id": 2,
                "name": "奖品1",
                "images": "http://image2.fqparty.com/image/md.png",
                "desc": "音豆*1",
                "rate": "26.30",
                "price": 1,
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 1
                }
            }, {
                "id": 3,
                "name": "奖品2",
                "images": "http://image2.fqparty.com/image/md.png",
                "desc": "音豆*5",
                "price": 5,
                "rate": "11.40",
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 5
                }
            }, {
                "id": 4,
                "name": "奖品3",
                "images": "http://image2.fqparty.com/image/md.png",
                "desc": "音豆*10",
                "price": 10,
                "rate": "7.50",
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 10
                }
            }, {
                "id": 5,
                "name": "奖品4",
                "images": "http://image2.fqparty.com/image/md.png",
                "desc": "音豆*20",
                "price": 20,
                "rate": "3.60",
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 20
                }
            }, {
                "id": 6,
                "name": "奖品5",
                "images": "http://image2.fqparty.com/image/md.png",
                "desc": "音豆*50",
                "price": 50,
                "rate": "1.30",
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 50
                }
            }, {
                "id": 7,
                "name": "奖品6",
                "images": "http://image2.fqparty.com/image/md.png",
                "desc": "音豆*100",
                "price": 100,
                "rate": "0.45",
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 100
                }
            }, {
                "id": 8,
                "name": "奖品7",
                "images": "http://image2.fqparty.com/image/md.png",
                "desc": "音豆*500",
                "price": 500,
                "rate": "0.05",
                "deliveryAsset": {
                    "assetId": "user:bean",
                    "count": 500
                }
            }],
            "rewardPool": {
                "poolId": 1,
                "type": "newer",
                "sort": 2,
                "gifts": [
                    [
                        1,
                        73080
                    ],
                    [
                        2,
                        20000
                    ],
                    [
                        3,
                        5000
                    ],
                    [
                        4,
                        1000
                    ],
                    [
                        5,
                        500
                    ],
                    [
                        6,
                        300
                    ],
                    [
                        7,
                        100
                    ],
                    [
                        8,
                        20
                    ]
                ]
            }
        }
    ';


    public static function loadConf()
    {
        return json_decode(self::$CONF, true);
    }
}