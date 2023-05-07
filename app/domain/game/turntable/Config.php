<?php


namespace app\domain\game\turntable;


use app\domain\exceptions\FQException;
use think\facade\Log;

class Config
{
    public static $TURNTABLE_CONF = '{
        "count":{
            "default":[1, 10, 66],
            "custom":[5, 200]
        },
        "isOpen":1,
        "fullPublicGiftValue":500,
        "fullFlutterGiftValue":1000,
        "turntables": [{
                "turntableId":1,
                "name":"初级",
                "price":20,
                "inJinliGiftValue":500,
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
                            [250, 35888]
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
                            [250, 35888]
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
                            [250, 35888]
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
                            [250, 35888]
                        ]
                    }
                ]
            },
            {
                "turntableId":2,
                "name":"高级",
                "price":100,
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
                            [250, 35888]
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
                            [250, 35888]
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
                            [250, 35888]
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
                            [250, 35888]
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
        $conf = \app\domain\Config::getInstance()->getConfigByKey('turntable_conf');
        if (empty($conf)){
            Log::error(sprintf('getBoxConf NotConfig'));
            throw new FQException('没有转盘配置', 500);
//            return json_decode(self::$TURNTABLE_CONF, true);

        }
        return $conf;
    }

    public function setBoxConf($conf) {
        \app\domain\Config::getInstance()->setConfigByKey('turntable_conf', $conf);
    }
}