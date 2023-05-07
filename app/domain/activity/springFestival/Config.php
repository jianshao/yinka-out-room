<?php


namespace app\domain\activity\springFestival;


class Config
{
    public static $CONF = '
        {
            "startTime":"2022-01-31 00:00:00",
            "stopTime":"2022-02-07 23:59:59",
            "rate":1,
            "poolKey":"blessing_pool",
            "activityFallingChance":{
                "turntable:2":100,
                "turntable:1":60,
                "breakBox:3":30,
                "breakBox:2":10,
                "breakBox:1":2
            },
            "goldBarArea":[
                15
            ],
            "coupletArea":[
                1,2,3,4,5,6,7,8,9,10,11,12,13,14
            ],
            "bangerArea":[
                16,17,18,19,20
            ],
            "bangerPartitionRate":{
                "16":0.35,"17":0.25,"18":0.18,"19":0.13,"20":0.09
            },
            "bangerTitle":{
                "16":"玉虎迎新",
                "17":"万户欢",
                "18":"千家乐",
                "19":"玉虎",
                "20":"金牛"
            },
            "exchangeRules":[
                {
                    "id" : 15,
                    "displayName":"金条",
                    "type" : "bank",
                    "count" :1,
                    "consume":[
                        1,2,3,4,5,6,7,8,9,10,11,12,13,14
                    ]
                },
                {
                    "id" : 16,
                    "displayName" : "紫爆竹",
                    "type" : "bank",
                    "count" :1,
                    "consume":[
                        8,9,10,11
                    ]
                },
                {
                    "id" : 17,
                    "displayName" : "红爆竹",
                    "type" : "bank",
                    "count" :1,
                    "consume":[
                        12,13,14
                    ]
                },
                {
                    "id" : 18,
                    "displayName" : "蓝爆竹",
                    "type" : "bank",
                    "count" :1,
                    "consume":[
                        5,6,7
                    ]
                },
                {
                    "id" : 19,
                    "displayName" : "绿爆竹",
                    "type" : "bank",
                    "count" :1,
                    "consume":[
                        8,9
                    ]
                },
                {
                    "id":20,
                    "displayName" : "金爆竹",
                    "type" : "bank",
                    "count" :1,
                    "consume":[
                        1,2
                    ]
                },
                {
                    "id":258,
                    "displayName" : "新年头像框",
                    "type" : "prop",
                    "count" :1,
                    "canConsume":[
                        1,2,3,4,5,6,7,8,9,10,11,12,13,14
                    ]
                },
                {
                    "id":259,
                    "displayName" : "新年座驾",
                    "type" : "prop",
                    "count" :1,
                    "canConsume":[
                        1,2,3,4,5,6,7,8,9,10,11,12,13,14
                    ]
                }
            ],
            "userBank":{
                "1":"金",
                "2":"牛",
                "3":"送",
                "4":"旧",
                "5":"千",
                "6":"家",
                "7":"乐",
                "8":"玉",
                "9":"虎",
                "10":"迎",
                "11":"新",
                "12":"万",
                "13":"户",
                "14":"欢",
                "15":"金条",
                "16":"紫爆竹",
                "17":"红爆竹",
                "18":"蓝爆竹",
                "19":"绿爆竹",
                "20":"金爆竹"
            },
            "rewardPool":{
					"poolId": 1,
					"type": "newer",
					"sort": 2,
					"gifts": [
						[
							1,
							10000
						],
						[
							2,
							66666
						],
						[
							3,
							10
						],
						[
							4,
							120000
						],
						[
							5,
							60000
						],
						[
							6,
							40000
						],
						[
							7,
							800
						],
                        [
                            8,
                            3000
                        ],
                        [
                            9,
                            66666
                        ],
                        [
                            10,
                            40000
                        ],
                        [
                            11,
                            100
                        ],
                        [
                            12,
                            30000
                        ],
                        [
                            13,
                            18888
                        ],
                        [
                            14,
                            200
                        ]
					]
				}
        }
    ';
    //                "15":{[1,2,3,4,5,6,7,8,9,10,11,12,13,14]}},
    //                "16":{[8,9,10,11]},
    //                "17":{[12,13,14]},
    //                "18":{[5,6,7]},
    //                "19":{[8,9]},
    //                "20":{[1,2]},
    //                "144":{[1],[2],[3],[4],[5],[6],[7],[8],[9],[10],[11],[12],[13],[14]},
    //                "171":{[1],[2],[3],[4],[5],[6],[7],[8],[9],[10],[11],[12],[13],[14]}
    //                print_r(array_column($config['exchangeRules'],null, 'id'));die;

    public static function loadConf() {
        return json_decode(self::$CONF, true);
    }
}