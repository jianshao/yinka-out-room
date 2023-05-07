<?php


namespace app\domain\game\box2;


class Config
{
    public static $BOX2_CONF = '{
        "count":{
            "default":[1, 10, 66],
            "custom":[5, 200]
        },
        "goodsId": 46,
        "isOpen":1,
        "priceAssetId":"bank:game:score",
        "fullPublicGiftValue":500,
        "fullFlutterGiftValue":1000,
        "boxes": [{
                "boxId":1,
                "name":"莫提斯宝箱",
                "price":20,
                "inJinliGiftValue":500,
                "special":{
                    "gifts":[372],
                    "maxProgress":1500,
                    "maxPoolValue":1500,
                    "giftValue":1500,
                    "giftWeight":10000
                },
                "pools":[
                    {
                        "poolId":1,
                        "type":"newer",
                        "sort":1,
                        "condition":[
                            {
                                "consume":[0, 500]
                            },
                            {
                                "consume":[500, 1000],
                                "rate":[0, 1.08]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":2,
                        "type":"newer",
                        "sort":2,
                        "condition":[
                            {
                                "consume":[0, 2000]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":3,
                        "type":"daily",
                        "sort":3,
                        "condition":[
                            {
                                "consume":[0, 1500]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":4,
                        "type":"daily",
                        "sort":4,
                        "condition":[
                            {
                                "consume":[0, 20000]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    }
                ]
            },
            {
                "boxId":2,
                "name":"宙斯宝箱",
                "price":100,
                "special":{
                    "gifts":[372],
                    "maxProgress":1500,
                    "maxPoolValue":1500,
                    "giftValue":1500,
                    "giftWeight":10000
                },
                "pools":[
                    {
                        "poolId":1,
                        "type":"newer",
                        "sort":1,
                        "condition":[
                            {
                                "consume":[0, 500]
                            },
                            {
                                "consume":[500, 1000],
                                "rate":[0, 1.08]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":2,
                        "type":"newer",
                        "sort":2,
                        "condition":[
                            {
                                "consume":[0, 2000]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":3,
                        "type":"daily",
                        "sort":3,
                        "condition":[
                            {
                                "consume":[0, 1500]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":4,
                        "type":"daily",
                        "sort":4,
                        "condition":[
                            {
                                "consume":[0, 20000]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    }
                ]
            },
            {
                "boxId":3,
                "name":"盖亚宝箱",
                "price":600,
                "special":{
                    "gifts":[372],
                    "maxProgress":1500,
                    "maxPoolValue":1500,
                    "giftValue":1500,
                    "giftWeight":10000
                },
                "pools":[
                    {
                        "poolId":1,
                        "type":"newer",
                        "sort":1,
                        "condition":[
                            {
                                "consume":[0, 500]
                            },
                            {
                                "consume":[500, 1000],
                                "rate":[0, 1.08]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":2,
                        "type":"newer",
                        "sort":2,
                        "condition":[
                            {
                                "consume":[0, 2000]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":3,
                        "type":"daily",
                        "sort":3,
                        "condition":[
                            {
                                "consume":[0, 1500]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
                    },
                    {
                        "poolId":4,
                        "type":"daily",
                        "sort":4,
                        "condition":[
                            {
                                "consume":[0, 20000]
                            }
                        ],
                        "gifts":[
                            [384, 1],
                            [396, 1],
                            [367, 6],
                            [369, 18],
                            [336, 788],
                            [362, 1388],
                            [294, 9688],
                            [250, 35888],
                            [251, 35888]
                        ]
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
        return \app\domain\Config::getInstance()->getConfigByKey('box2_conf');
//        return json_decode($conf, true);
//        return json_decode(self::$BOX2_CONF, true);
    }

    public function setBoxConf($conf) {
        return \app\domain\Config::getInstance()->setConfigByKey('box2_conf', $conf);
    }
}