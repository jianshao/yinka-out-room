<?php

namespace app\domain;

use app\common\RedisCommon;
use app\utils\ArrayUtil;
use think\facade\Log;

class Config
{
    public static $VIP_CONF = '
        {
            "levels":[
                {
                    "level":1,
                    "privilegeDesc":[
                        {
                            "title":"会员标识",
                            "pic":"/privilege/20200805/4efe8bbca9154d4628f2c719c80f0baa.png",
                            "previewPic":"/banner/20200806/a3cd9068bf9e2323de2e5393aadf6a02.png",
                            "content":"在会员有效期内，专属会员标识将出现在您的个人资料页、进房信息、公屏聊天中，突 显尊贵身份。",
                            "status":1
                        },
                        {
                            "title":"炫彩昵称",
                            "pic":"/privilege/20200805/c62bd7a8a9dbb88ef4711d55d1ee7ebc.png",
                            "previewPic":"/banner/20200806/a3cd9068bf9e2323de2e5393aadf6a02.png",
                            "content":"开通会员可享受昵称炫色，让您在麦位中与众不同。",
                            "status":1
                        },
                        {
                            "title":"谁看过我",
                            "pic":"/privilege/20200805/ac9d7ab37748ec635cd1df1fcb05c8a5.png",
                            "previewPic":"/banner/20200806/ea1c39afe8fa7b5f8a8964e7874b8256.png",
                            "content":"开通会员可看到每日访客信息。",
                            "status":1
                        },
                        {
                            "title":"动态头像",
                            "pic":"/privilege/20200805/ae42ee79f8f64ebced5bf42209ec0c6a.png",
                            "previewPic":"/banner/20200806/989c4b7cab6d20a783bc77fb5c3f487a.png",
                            "content":"在会员有效期间，动态头像会展示到多个您头像出现的地方。",
                            "status":1
                        },
                        {
                            "title":"10张形象图",
                            "pic":"/privilege/20200805/50d710c00fbf7324e125d29cb96c8295.png",
                            "previewPic":" /banner/20200806/0be671cdf853542970984370331ed349.png",
                            "content":"在会员有效期间，您将享受10张形象照片特权。",
                            "status":1
                        },
                        {
                            "title":"专属装扮",
                            "pic":"/privilege/20200805/e130a3c3adad2f21d7e5aa9f1d73b419.png",
                            "previewPic":"/banner/20200806/cb4b5e084c103331d50f4ac4f1e4b4de.png",
                            "content":"在会员有效期间，您可使用会员专属装扮。",
                            "status":1
                        },
                        {
                            "title":"专属礼物",
                            "pic":"/privilege/20200805/3ddcb8e7b6ce69268a048de2d08022a0.png",
                            "previewPic":"/banner/20200806/1744d9167bf01dc6cc1dc9d34a525470.png",
                            "content":"您将获得会员专享的礼物，彰显您的尊贵会员身份。",
                            "status":1
                        }
                    ],
                    "privilegeAssets":[
                        {
                            "assetId":"prop:126",
                            "count":1
                        }
                    ]
                },
                {
                    "level":2,
                    "privilegeDesc":[
                        {
                            "title":"消息气泡",
                            "pic":"/privilege/20200805/7a310f2ee37aabef08ac7c419c3af658.png",
                            "previewPic":"/banner/20200806/6f77e40cd88c9a681ed98dcbd86bcd8e.png",
                            "content":"在会员有效期间，您可使用专属消息气泡，让您的聊天与众不同。",
                            "status":1
                        },
                        {
                            "title":"专属表情",
                            "pic":"/privilege/20200805/d683241b18f3c8db6ecc1dc1b473fcf3.png",
                            "previewPic":"/banner/20200806/c6922145f2c4275c484f89ec960dd0bf.png",
                            "content":"在会员有效期间，您可使用专属会员表情，彰显您的尊贵身份。",
                            "status":1
                        },
                        {
                            "title":"专属背景",
                            "pic":"/privilege/20200805/2e9756481ed230b98dd4b99f41b3085b.png",
                            "previewPic":"/banner/20200806/11dfa80ecef62add427ba4c71cbc58c1.png",
                            "content":"在会员有效期间，您可使在房间内使用会员专属背景。",
                            "status":1
                        },
                        {
                            "title":"成长值加速",
                            "pic":"/privilege/20200805/0e4a8db296b30f1eae5210446e9e5879.png",
                            "previewPic":"/banner/20200806/b5dcfb8381699678ed3069f1637ff150.png",
                            "content":"在会员有效期间，您将享受成长值加速。",
                            "status":1
                        }
                    ],
                    "privilegeAssets":[
                        {
                            "assetId":"prop:127",
                            "count":1
                        }
                    ]
                }
            ]
        }
    ';

    public static $GOODS_CONF = '
        {
            "goods":[
                {
                    "goodsId":46,
                    "name":"徽章",
                    "desc":"徽章",
                    "unit":"个",
                    "image":"/gift/20201107/df212e60a10130ac31d98985c0e68770.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"score",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"user:bean",
                                "count":10
                            }
                        }
                    ],
                    "content":{
                        "assetId":"bank:game:score",
                        "count":10
                    }
                },
                {
                    "goodsId":1,
                    "name":"小清新",
                    "desc":"小清新",
                    "unit":"天",
                    "image":"/attire/20200717/426c63909902b18b591a2566a0da84b8.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"avatar",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"user:bean",
                                "count":49
                            }
                        },
                        {
                            "count":7,
                            "price":{
                                "assetId":"user:bean",
                                "count":333
                            }
                        },
                        {
                            "count":30,
                            "price":{
                                "assetId":"user:bean",
                                "count":1399
                            }
                        }
                    ],
                    "content":{
                        "assetId":"prop:118",
                        "count":1
                    }
                },
                {
                    "goodsId":2,
                    "name":"蝶雨恋",
                    "desc":"蝶雨恋",
                    "unit":"天",
                    "image":"/attire/20200715/91a432b1ae799a30c13a5aec1623c8cb.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"avatar",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"user:bean",
                                "count":49
                            }
                        },
                        {
                            "count":7,
                            "price":{
                                "assetId":"user:bean",
                                "count":333
                            }
                        },
                        {
                            "count":30,
                            "price":{
                                "assetId":"user:bean",
                                "count":1399
                            }
                        }
                    ],
                    "content":{
                        "assetId":"prop:117",
                        "count":1
                    }
                },
                {
                    "goodsId":3,
                    "name":"满天星",
                    "desc":"满天星",
                    "unit":"天",
                    "image":"/attire/20200715/5671d64a39a7fb76245e16cb5bcaa02e.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"avatar",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"user:bean",
                                "count":49
                            }
                        },
                        {
                            "count":7,
                            "price":{
                                "assetId":"user:bean",
                                "count":333
                            }
                        },
                        {
                            "count":30,
                            "price":{
                                "assetId":"user:bean",
                                "count":1399
                            }
                        }
                    ],
                    "content":{
                        "assetId":"prop:116",
                        "count":1
                    }
                },
                {
                    "goodsId":4,
                    "name":"年会员头像框",
                    "desc":"年会员头像框",
                    "unit":"天",
                    "image":"/attire/20200807/f580204f0ebab32c8515821a238fdcc6.png",
                    "animation":"",
                    "buyType":"svip",
                    "type":"avatar",
                    "state":1,
                    "content":{
                        "assetId":"prop:127",
                        "count":1
                    }
                },
                {
                    "goodsId":5,
                    "name":"会员头像框",
                    "desc":"会员头像框",
                    "unit":"天",
                    "image":"/attire/20200807/4848444f15b8f98685e6eb4902ea9fbe.png",
                    "animation":"",
                    "buyType":"vip",
                    "type":"avatar",
                    "state":1,
                    "content":{
                        "assetId":"prop:126",
                        "count":1
                    }
                },
                {
                    "goodsId":6,
                    "name":"皇冠头像框",
                    "desc":"开宝箱可获得",
                    "unit":"天",
                    "image":"/attire/20200613/a3110ea8f97470aa850a913cf9823619.png",
                    "animation":"",
                    "buyType":"silverBox",
                    "type":"avatar",
                    "state":1,
                    "content":{
                        "assetId":"prop:1",
                        "count":1
                    }
                },
                {
                    "goodsId":7,
                    "name":"爱心头像框",
                    "desc":"开宝箱可获得",
                    "unit":"天",
                    "image":"/attire/20200613/2d4975a940b95f4fbe401f6b97e93b64.png",
                    "animation":"",
                    "buyType":"silverBox",
                    "type":"avatar",
                    "state":1,
                    "content":{
                        "assetId":"prop:2",
                        "count":1
                    }
                },
                {
                    "goodsId":8,
                    "name":"萌新头像框",
                    "desc":"首充头像框",
                    "unit":"天",
                    "image":"/background_image/20200609/5378075bdd9c004d4286fbb9e04130bb.png",
                    "animation":"",
                    "buyType":"silverBox",
                    "type":"avatar",
                    "state":1,
                    "content":{
                        "assetId":"prop:5",
                        "count":1
                    }
                },
                {
                    "goodsId":9,
                    "name":"年会员气泡",
                    "desc":"年会员气泡",
                    "unit":"天",
                    "image":"/attire/20200613/2d4975a940b95f4fbe401f6b97e93b64.png",
                    "imageAndroid":"/attire/20201009/7fd4e1779c7cd30713dd464e0d33d9d3.png",
                    "animation":"",
                    "buyType":"svip",
                    "type":"bubble",
                    "state":1,
                    "content":{
                        "assetId":"prop:138",
                        "count":1
                    }
                },
                {
                    "goodsId":10,
                    "name":"梦幻之夜",
                    "desc":"梦幻之夜",
                    "unit":"天",
                    "image":"/attire/20200903/579f3b016279b65704db77bc53f18e88.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"coin",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"user:coin",
                                "count":888
                            }
                        }
                    ],
                    "content":{
                        "assetId":"prop:129",
                        "count":1
                    }
                },
                {
                    "goodsId":11,
                    "name":"深邃之夜",
                    "desc":"深邃之夜",
                    "unit":"天",
                    "image":"/attire/20200903/6949a908897d0ea11fc7e37a28c40c97.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"coin",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"user:coin",
                                "count":888
                            }
                        }
                    ],
                    "content":{
                        "assetId":"prop:132",
                        "count":1
                    }
                },
                {
                    "goodsId":12,
                    "name":"海鲸之梦",
                    "desc":"海鲸之梦",
                    "unit":"个",
                    "image":"/gift/20201107/ffb987d44e0ba135ffbb8a18c5e74403.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"ore",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"ore:fossil",
                                "count":3
                            }
                        }
                    ],
                    "content":{
                        "assetId":"gift:396",
                        "count":1
                    }
                },
                {
                    "goodsId":13,
                    "name":"火箭",
                    "desc":"火箭",
                    "unit":"个",
                    "image":"/upload/20190628/e4b20845a330728e821f3923e67a53f4.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"ore",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"ore:gold",
                                "count":3
                            }
                        }
                    ],
                    "content":{
                        "assetId":"gift:231",
                        "count":1
                    }
                },
                {
                    "goodsId":14,
                    "name":"梦幻别墅",
                    "desc":"梦幻别墅",
                    "unit":"天",
                    "image":"/gift/20200814/9a3341c3be2c127a8d6324973917e363.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"ore",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"ore:silver",
                                "count":3
                            }
                        }
                    ],
                    "content":{
                        "assetId":"gift:372",
                        "count":1
                    }
                },
                {
                    "goodsId":15,
                    "name":"海洋之心",
                    "desc":"海洋之心",
                    "unit":"天",
                    "image":"/gift/20201107/df212e60a10130ac31d98985c0e68770.png",
                    "animation":"",
                    "buyType":"buy",
                    "type":"ore",
                    "state":1,
                    "priceList":[
                        {
                            "count":1,
                            "price":{
                                "assetId":"ore:iron",
                                "count":3
                            }
                        }
                    ],
                    "content":{
                        "assetId":"gift:397",
                        "count":1
                    }
                }
            ]
        }
    ';

    public static $MALL_CONF = '
        {
            "bean":{
                "areas":[
                    {
                        "type":"avatar",
                        "displayName":"头像框",
                        "shelves":[
                            {
                                "displayName":"商城装扮",
                                "goodsIds":[1,2,3]
                            },
                            {
                                "displayName":"VIP/SVIP装扮",
                                "goodsIds":[4,5]
                            },
                            {
                                "displayName":"活动装扮",
                                "goodsIds":[6,7,8]
                            }
                        ]
                    },
                    {
                        "type":"bubble",
                        "displayName":"气泡框",
                        "shelves":[
                            {
                                "displayName":"VIP/SVIP装扮",
                                "goodsIds":[9]
                            }
                        ]
                    },
                    {
                        "type":"voiceprint",
                        "displayName":"麦位光圈",
                        "shelves":[
                            {
                                "displayName":"VIP/SVIP装扮",
                                "goodsIds":[]
                            }
                        ]
                    },
                    {
                        "type":"mount",
                        "displayName":"坐骑",
                        "shelves":[
                            {
                                "displayName":"VIP/SVIP装扮",
                                "goodsIds":[]
                            }
                        ]
                    }
                ]
            },
            "coin":{
                "areas":[
                    {
                        "type":"coin",
                        "displayName":"头像框",
                        "shelves":[
                            {
                                "displayName":"金币商城",
                                "goodsIds":[10,11]
                            }
                        ]
                    }
                ]
            },
            "ore":{
                "areas":[
                    {
                        "type":"ore",
                        "displayName":"礼物",
                        "shelves":[
                            {
                                "displayName":"矿石商城",
                                "goodsIds":[12,13,14,15]
                            }
                        ]
                    }
                ]
            }
        }
    ';

    public static $GIFT_CONF = '{
        "gifts":[
            {
                "giftId":236,
                "name":"爱的巨轮",
                "unit":"个",
                "charm":5200,
                "image":"/gift/20201007/ddf82bb375e5bdce25b46b50681aa432.png",
                "animation":"",
                "giftAnimation":"/gift/20201007/7b18a31e80dd032a29da43e0cb182c0f.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":5200
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":242,
                "name":"烟花城堡",
                "unit":"个",
                "charm":33440,
                "image":"/upload/20190712/bbecd5bc21cecb63bb7a66595137b2d0.png",
                "animation":"",
                "giftAnimation":"/upload/image/chengbao.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":33440
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":251,
                "name":"比心",
                "unit":"个",
                "charm":1,
                "image":"/gift/20200711/61d78c5de0ae96181fc1db2349d19dee.png",
                "animation":"/gift/20200711/3d0f3382d6083a663f40fb92d36b1d98.gif",
                "giftAnimation":"/gift/20200711/e7ad53a3071ad72f5a0de4f5a3df7f52.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":1
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":250,
                "name":"棒棒糖",
                "unit":"个",
                "charm":9,
                "image":"/gift/20200711/693cefee016f3285874d908459577dd0.png",
                "animation":"/gift/20200711/99048b89ba8d5349ad9f88f9a2c2a3a1.gif",
                "giftAnimation":"/gift/20200711/948ea335c567c7d4ed052ce97b4f4328.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":9
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":290,
                "name":"漂流瓶",
                "unit":"个",
                "charm":1888,
                "image":"/upload/20190820/f17135a43ca2dcf891db68bb92017b68.png",
                "animation":"",
                "giftAnimation":"/upload/20190820/7187f42ec3d34addc34fb816980f6f23.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":1888
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":299,
                "name":"游乐园",
                "unit":"个",
                "charm":18888,
                "image":"/upload/20190820/5bc2303bae1a6e7551306d4614f104f5.png",
                "animation":"",
                "giftAnimation":"/upload/20190820/63031bd3c03c687a953153e9fc53a171.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":18888
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":336,
                "name":"情书",
                "unit":"个",
                "charm":520,
                "image":"/gift/20200314/91ece47addb31bdae7888820ed87b5f4.png",
                "animation":"",
                "giftAnimation":"/gift/20200314/5683b1d3c30d6b113b1e4132e5fcb950.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":520
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":370,
                "name":"萤火虫之恋",
                "unit":"个",
                "charm":999,
                "image":"/gift/20200813/7fc59ee5d7379b0e65142c61262f48af.png",
                "animation":"",
                "giftAnimation":"/gift/20200813/ad08b12fcda00a214ced269ba7234417.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":999
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":343,
                "name":"仙女棒",
                "unit":"个",
                "charm":52,
                "image":"/gift/20200711/0d395170521cce3fdd8fad7f22683d2f.png",
                "animation":"/gift/20200407/9d4523a8f9b0002e1fb90d93cab765ad.gif",
                "giftAnimation":"/gift/20200407/12524b1cd781e5e62c81e8575338f304.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":52
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":257,
                "name":"爱心熊",
                "unit":"个",
                "charm":111,
                "image":"/gift/20200711/a07fc0b2a13be5f7760518c1107dbc44.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":294,
                "name":"纸鹤",
                "unit":"个",
                "charm":111,
                "image":"/upload/20190820/8c829d095a42a03e083f24017e54b57d.png",
                "animation":"",
                "giftAnimation":"",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":376,
                "name":"幸运盒子",
                "unit":"个",
                "charm":0,
                "vipLevel":0,
                "dukeLevel":0,
                "image":"/gift/20200903/19d946e1c06b0fb775062323dcb0f1aa.png",
                "animation":"/gift/20200903/8ccfa391cb714078e5ea301ccb44a2f9.gif",
                "giftAnimation":"/gift/20200903/62eac083c89df0a661e3f7e66260d8c5.svga",
                "price":{
                    "assetId":"user:bean",
                    "count":100
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170,
                "box":[
                    {
                        "giftId":250,
                        "weight":100
                    },
                    {
                        "giftId":251,
                        "weight":100
                    },
                    {
                        "giftId":257,
                        "weight":100
                    },
                    {
                        "giftId":343,
                        "weight":100
                    }
                ]
            },
            {
                "giftId":366,
                "name":"彩虹城堡",
                "unit":"个",
                "charm":111,
                "image":"/gift/20200811/337257bf32ee635e132b7b1fa95e7740.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":368,
                "name":"试音卡",
                "unit":"个",
                "charm":1,
                "image":"/gift/20200730/7117d7e508776098e2b7170983b38c36.png",
                "animation":"/gift/20200730/dcc7aebb79c91b1e0cd0afb22a486237.gif",
                "giftAnimation":"/gift/20200730/bd9fa4d87f52f6a19307bb3c79a01037.svga",
                "intro":"",
                "classification":"",
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":396,
                "name":"海鲸之梦",
                "unit":"个",
                "charm":33440,
                "image":"/gift/20201107/ffb987d44e0ba135ffbb8a18c5e74403.png",
                "animation":"",
                "giftAnimation":"/gift/20201107/029b9f06314bcdd96d805ef97f935a8c.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":33440
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":360,
                "name":"小星星",
                "unit":"个",
                "charm":0,
                "image":"/gift/20200814/dc2fb670b42d89f5beb79009ae62a0cf.png",
                "animation":"/gift/20200814/24b86a413e462dfb4d3238a22bf2067e.gif",
                "giftAnimation":"/gift/20200814/c4317517a5d130fbae9fc7bdd20ec6c0.svga",
                "intro":"",
                "classification":"",
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":397,
                "name":"海洋之心",
                "unit":"个",
                "charm":111,
                "image":"/gift/20201107/df212e60a10130ac31d98985c0e68770.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":666
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":362,
                "name":"玻璃风车",
                "unit":"个",
                "charm":188,
                "image":"/gift/20200711/ed042563a144e5ca784ce29513539e6d.png",
                "animation":"/gift/20200711/be932e7a445d5993b5fd89f21f575117.gif",
                "giftAnimation":"/gift/20200711/3b695396bb2237b206f2cf7fc4a2d600.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":188
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":363,
                "name":"游艇",
                "unit":"个",
                "charm":111,
                "image":"/gift/20200711/8921e5b93a25d4701aa86c0fa529babb.png",
                "animation":"/gift/20200711/b52209fd4814cc62c4bfcc16ba5c7221.gif",
                "giftAnimation":"/gift/20200711/de708ab8a7337869fec204335e0c96e5.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":367,
                "name":"爱的背包",
                "unit":"个",
                "charm":3344,
                "image":"/gift/20200813/99e06cae32f1fc4bbe1e54beb38b83f6.png",
                "animation":"",
                "giftAnimation":"/gift/20200813/3961d48c4307f3ca706bc5c08428eb8c.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":3344
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":369,
                "name":"泡泡枪",
                "unit":"个",
                "charm":777,
                "image":"/gift/20200813/18b14a0acde53e1872bd0f9237e2b5fe.png",
                "animation":"",
                "giftAnimation":"/gift/20200813/4a8c5d800c6c61034741c2e1d841bbca.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":777
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":383,
                "name":"海底世界",
                "unit":"个",
                "charm":111,
                "image":"/gift/20201014/b9784ccbd20302fed6b63d959e99f78a.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":373,
                "name":"爱在七夕",
                "unit":"个",
                "charm":111,
                "image":"/gift/20200824/302b7698194503f3fd053746a49c654d.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":372,
                "name":"梦幻别墅",
                "unit":"个",
                "charm":111,
                "image":"/gift/20200814/9a3341c3be2c127a8d6324973917e363.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":371,
                "name":"银河战舰",
                "unit":"个",
                "charm":111,
                "image":"/gift/20200814/83c0fced7abad2ea2c2bdb3803440a65.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":231,
                "name":"火箭",
                "unit":"个",
                "charm":111,
                "image":"/upload/20190628/e4b20845a330728e821f3923e67a53f4.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170,
                "senderAssets":[
                    {"assetId":"user:energy", "count":50}
                ],
                "receiverAssets":[
                    {"assetId":"user:bean", "count":1}
                ]
            },
            {
                "giftId":384,
                "name":"浪漫陪伴",
                "unit":"个",
                "charm":15200,
                "image":"/gift/20201026/c5ea074b6b43de2dee371770d7a38987.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":15200
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":364,
                "name":"超级火箭",
                "unit":"个",
                "charm":111,
                "image":"/gift/20200721/a4ac7055e226d9f0ddf8686b41e8a1b8.png",
                "animation":"/gift/20200711/9dc37519fb39a850ba579a51f9240e75.gif",
                "giftAnimation":"/gift/20200711/a991c726192d442546c8ab8128199fe3.svga",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":111
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":403,
                "name":"iphone12-128g",
                "unit":"个",
                "charm":67999,
                "image":"/gift/20201113/d51e77f1b0d4cef7523869bd5deec760.png",
                "animation":"",
                "giftAnimation":"",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":67999
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            },
            {
                "giftId":408,
                "name":"雪人",
                "unit":"个",
                "charm":25,
                "image":"/gift/20201203/2a1d621b182cb805d915cd0c3ba2fc4c.png",
                "animation":"",
                "giftAnimation":"",
                "intro":"",
                "classification":"",
                "price":{
                    "assetId":"user:bean",
                    "count":25
                },
                "tags":"",
                "createTime":1599127170,
                "updateTime":1599127170
            }
        ]
    }';

    public static $GIFT_PANELS = '
    {
        "panels":[
            {
                "name":"gift",
                "displayName":"礼物",
                "gifts":[376,251,372,371]
            },
            {
                "name":"activity",
                "displayName":"活动",
                "gifts":[376,251]
            },
            {
                "name":"privilege",
                "displayName":"特权",
                "gifts":[376,251]
            }
        ],
        "private_chat_panels":[
            {
                "name":"gift",
                "displayName":"礼物",
                "gifts":[376,251]
            },
            {
                "name":"activity",
                "displayName":"活动",
                "gifts":[364]
            },
            {
                "name":"privilege",
                "displayName":"特权",
                "gifts":[371,372]
            }
        ],
        "gameGifts":[364]
    }';

    public static $GIFT_WALL = '
    {
        "walls":[
            {
                "name":"final",
                "displayName":"终极礼物",
                "gifts":[366,396,242,363,383]
            },
            {
                "name":"best",
                "displayName":"极品礼物",
                "gifts":[373,372,371,231,384,364]
            },
            {
                "name":"normal",
                "displayName":"普通礼物",
                "gifts":[376, 251]
            }
        ]
    }';

    public $PROP_CONF = <<<str
[{"kindId":1,"name":"皇冠头像框","desc":"开宝箱可获得","image":"/attire/20200613/a3110ea8f97470aa850a913cf9823619.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":0,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 1},{"kindId":2,"name":"爱心头像框","desc":"开宝箱可获得","image":"/attire/20200613/2d4975a940b95f4fbe401f6b97e93b64.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":0,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 2},{"kindId":5,"name":"萌新头像框","desc":"首充头像框","image":"/background_image/20200609/5378075bdd9c004d4286fbb9e04130bb.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":0,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 3},{"kindId":97,"name":"第一名","desc":"榜单第一名头像框","image":"/images/dym.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":0,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":98,"name":"第二名","desc":"榜单第二名头像框","image":"/images/dem.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":0,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":99,"name":"第三名","desc":"榜单第三名头像框","image":"/images/dsm.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":0,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":116,"name":"满天星","desc":"满天星","image":"/attire/20200715/5671d64a39a7fb76245e16cb5bcaa02e.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1594823142,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 4},{"kindId":117,"name":"蝶语恋","desc":"蝶语恋","image":"/attire/20200715/91a432b1ae799a30c13a5aec1623c8cb.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1594823527,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 5},{"kindId":118,"name":"小清新","desc":"小清新","image":"/attire/20200717/426c63909902b18b591a2566a0da84b8.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1594823562,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":119,"name":"小海豚头像框","desc":"mua纪念头像框","image":"/attire/20200724/b992e398674fb7bdd8bb23ef4cd1f932.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1595596960,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":120,"name":"15级头像框","desc":"15级头像框","image":"/attire/20200728/c2eadea308d1c2dcfd39ebb85aef1227.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1595908228,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":121,"name":"35级头像框","desc":"35级头像框","image":"/attire/20200728/3b0bf978bc6a5971acd26c03aa7cdda6.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1595908257,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":122,"name":"45级头像框","desc":"45级头像框","image":"/attire/20200728/c79ef5f06731e63115528b54fb1b2cd1.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1595908280,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":123,"name":"65级头像框","desc":"65级头像框","image":"/attire/20200728/8974883085959b59b454c7e8a16b36f1.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1595908304,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":124,"name":"80级头像框","desc":"80级头像框","image":"/attire/20200728/11df93efca5bb233800341e0e3340109.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1595908328,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":126,"name":"会员头像框","desc":"会员头像框","image":"/attire/20200807/4848444f15b8f98685e6eb4902ea9fbe.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1596741166,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":127,"name":"年会员头像框","desc":"年会员头像框","image":"/attire/20200807/f580204f0ebab32c8515821a238fdcc6.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1596741198,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":129,"name":"梦幻之夜","desc":"梦幻之夜","image":"/attire/20200903/579f3b016279b65704db77bc53f18e88.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1599117199,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":132,"name":"深邃之夜","desc":"深邃之夜","image":"/attire/20200903/6949a908897d0ea11fc7e37a28c40c97.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1599127740,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":133,"name":"七夕头像框","desc":"七夕活动头像框","image":"/attire/20200903/a415ab8762e6f1f89f4e3775fdaff1c7.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20200903/fd26eba7b70556c028762745fba8876c.svga","color":"","createTime":1599141232,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":134,"name":"周星头像框（1）","desc":"周星活动头像框","image":"/attire/20201103/c050ff0d7d28cb79d2c3881b8842274b.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1604384629,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":135,"name":"万圣节头像框","desc":"万圣节活动头像框","image":"/attire/20201104/e6a25779f001bfa720c8e2b25807d8fe.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1604470562,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":136,"name":"南瓜灯头像框","desc":"南瓜灯头像框","image":"/attire/20201104/8ce07d770900dd26411f644dfb2c377b.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1604470718,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":137,"name":"精灵糖头像框","desc":"精灵糖头像框","image":"/attire/20201104/c2aeaa3dc540e1814e91bf64e3d25453.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1604470757,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":138,"name":"年会员气泡","desc":"年会员气泡","image":"/attire/20200927/1a991b63c6b425750b23d6b76896d303.png","imageAndroid":"/attire/20201009/7fd4e1779c7cd30713dd464e0d33d9d3.png","bubbleWordImage":"/attire/20201214/e084ff6bb185ab902c2ff28e14ce35e8.png","animation":"","color":"","createTime":1596740965,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"bubble","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":139,"name":"周星头像框","desc":"周星头像框","image":"/attire/20201109/549e13aca49863b6131da0e740a8bb41.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/037cf4953705d4d0f0e31c38df5268a1.svga","color":"","createTime":1604649658,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":140,"name":"靓丽女神","desc":"靓丽女神","image":"/attire/20201109/ca9e7d60e6652e1f47505b1bcf487a6e.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/581efdeb84e3288a7f1fe10d4a4c9562.svga","color":"","createTime":1604904592,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":141,"name":"团宠公主","desc":"团宠公主","image":"/attire/20201109/8f2dd6f110c0770ece512a64874eb77a.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/9d9ad260e9e260042313f528ab2d1030.svga","color":"","createTime":1604923325,"multiple":2.2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":142,"name":"灰姑娘头像框","desc":"灰姑娘头像框","image":"/attire/20201109/e03b492ad3b32f4d225a212a877ff4bc.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/9abb18424285e537b96ba5de281252fd.svga","color":"","createTime":1604924294,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":143,"name":"甜心少女","desc":"甜心少女","image":"/attire/20201109/42d9b5ed375d0f72875701934ef2e280.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/6e215cac11c62760ea6503e1c35cd56c.svga","color":"","createTime":1604924753,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":144,"name":"霸道总裁","desc":"霸道总裁","image":"/attire/20201109/597f3ccd0620b462c2f018d01413cf3f.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/cef3d5678c721bb4f5bc644ec9326709.svga","color":"","createTime":1604925816,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":145,"name":"帅气男神","desc":"帅气男神","image":"/attire/20201109/844bb88f5e6d7906873b26164f680b60.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/b69b30bd1b5d1ebd8efcc7315bd001ae.svga","color":"","createTime":1604925966,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":146,"name":"温柔暖男","desc":"温柔暖男","image":"/attire/20201109/005d0d5d84f1627e8c0265450d1bd964.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201109/04576be3ed8abb7fd8794ab5fa779b40.svga","color":"","createTime":1604926116,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":147,"name":"彩虹小马","desc":"彩虹小马","image":"/attire/20210202/6d68e0f8b927672c5bc49cc32ac62b22.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201111/9ee5ef7afdfe6e28d890535e0b96b8ef.svga","color":"","createTime":1605076870,"multiple":1.5,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":148,"name":"白虎","desc":"公爵专属","image":"/attire/20201214/f4328e3ccf5449afa681e8dfcb7d85c4.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201211/8f1fef6c323006a142585e4768de3f06.svga","color":"","createTime":1607335043,"multiple":1.7,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":149,"name":"朱雀","desc":"国王专属","image":"/attire/20201211/fdec14ca124b2a58220b47ae6e15e1ad.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1607336588,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"mount","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":150,"name":"白虎","desc":"公爵专属","image":"/attire/20201211/9ed2bd978d8a6748aa49f44f6245698c.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1607336513,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"mount","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":151,"name":"青蛇","desc":"骑士专属","image":"/attire/20201216/63947d5c8f7e770b45fb23103c081948.png","imageAndroid":"/attire/20201216/296be4c0874d58f3333ed478e0d3d684.png","bubbleWordImage":"/attire/20201216/02f749c3f6eade1a34a258976b6c5509.png","animation":"","color":"","createTime":1607426577,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"bubble","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":152,"name":"雪狐","desc":"游侠专属","image":"/attire/20201216/4ca5726bd5fca3f8c11e0ebf7c84c58f.png","imageAndroid":"/attire/20201216/8443085df1d6788c74344178dbfca639.png","bubbleWordImage":"/attire/20201216/9173fc99c9b15736d10bb7df584d4cbc.png","animation":"","color":"","createTime":1607426627,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"bubble","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":153,"name":"白虎","desc":"公爵专属","image":"/attire/20201216/6e3c020252c13424d22fffe2f05ba5c1.png","imageAndroid":"/attire/20201216/d86914dd5dfdc4ee753282141cc496f5.png","bubbleWordImage":"/attire/20201216/f9be079ea2af6e136d43f4ac48f560ed.png","animation":"","color":"","createTime":1607330724,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"bubble","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":154,"name":"伯爵声波","desc":"伯爵专属","image":"/attire/20201209/6c825ce350fd83052a490dac4540c5f5.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201209/0cdc8df1faf7d2dad87f079c8a7fb928.svga","color":"#7C83F1","createTime":1607498661,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"voiceprint","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":155,"name":"公爵声波","desc":"公爵专属","image":"/attire/20201209/8fbe652b9ac913f1c2db5d6415f180f6.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201209/dd5e588684f292f850449a2f07bb1181.svga","color":"#C09AEF","createTime":1607499380,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"voiceprint","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":156,"name":"国王声波","desc":"国王专属","image":"/attire/20201210/c0c99fca43152f92973f0babeaf297ad.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201210/220b9c118e15aa9e57cb98e25f6b8f0b.svga","color":"#F8B5FF","createTime":1607499480,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"voiceprint","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":157,"name":"朱雀","desc":"国王专属","image":"/attire/20201214/e1353876242857a3891d83def1b4ea86.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210126/ca11218c605ea74a8b97abc1185b4066.svga","color":"","createTime":1607687280,"multiple":1.9,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":158,"name":"玄武","desc":"伯爵专属","image":"/attire/20201216/9c8a0e72dc8e11d0d983dcd737214c54.png","imageAndroid":"/attire/20201216/35984912ccdd07ef09c0b941bf15279b.png","bubbleWordImage":"/attire/20201216/4e9da5d4a3de3039c14334362a0e6791.png","animation":"","color":"","createTime":1607754627,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"bubble","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":160,"name":"玄武","desc":"伯爵专属","image":"/attire/20201214/563031f080d3434d7bab6dec1aa1ded9.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201214/c9ffc9c48782d5b1cc0221cf13cfb2d9.svga","color":"","createTime":1607931642,"multiple":1.8,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":161,"name":"朱雀","desc":"国王专属","image":"/attire/20210218/5b8a8c4972a24707a561ebe8e66e3fba.png","imageAndroid":"/attire/20201216/b3344a5e125d8a07dd2e659387e004dc.png","bubbleWordImage":"/attire/20201216/c4ef9c9a0dc7632f0d7c9dcadaf5d5bc.png","animation":"","color":"","createTime":1607944000,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"bubble","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":162,"name":"小倔驴","desc":"商城装扮","image":"/attire/20201214/a3c88cdada521569d622a29d9a2a3b25.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201214/95952d6e775ce280b58a107bae233c22.svga","color":"","createTime":1607950830,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":163,"name":"胖憨猪","desc":"商城装扮","image":"/attire/20201214/1e2485be21e7bd91c0e7071a45362962.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201214/ecc3477dad614b57a99e274ebb4b9d13.svga","color":"","createTime":1607950892,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":164,"name":"青蛇","desc":"骑士专属","image":"/attire/20201215/993a41f8654eff73a11a7c88955b3565.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201215/98214f0349fe8f19c9db8f49d506285f.svga","color":"","createTime":1608031708,"multiple":2,"updateTime":0,"removeFormBagWhenDied":1,"showInBag":1,"type":"avatar","unit":{"type":"countMax1","displayName": "个"},"goodsId": 0},{"kindId":165,"name":"圣诞老人","desc":"活动专属","image":"/attire/20201221/5847b16225c9b5a000b9259e8e81f836.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201222/39556414d3bd29d1a0f4433a8e18b375.svga","color":"","createTime":1608519113,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":166,"name":"圣诞帽","desc":"活动专属","image":"/attire/20201221/c0e791dcd22b0e1138318035695e0db7.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201222/3c39d8db5f16afe7e5bf392fe3c85cad.svga","color":"","createTime":1608519153,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":167,"name":"圣诞麋鹿","desc":"活动专属","image":"/attire/20201221/8810d595e3b643bbe16359054d174bc8.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201222/21fdb53bfaaf4279a264ef589571b15b.svga","color":"","createTime":1608519209,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":168,"name":"圣诞雪花","desc":"活动专属","image":"/attire/20201221/b498cbb8eabf3bb1a1715fc5002e0494.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201222/df82cdfe83934acd7c227a30ce436021.svga","color":"","createTime":1608519316,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":169,"name":"麋鹿神车","desc":"商城装扮","image":"/attire/20201221/9e38e99e89c48546b6a87ccb3e525f98.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201221/1ad8dd491e92c1bc996b85af5515ed24.svga","color":"","createTime":1608540858,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":170,"name":"新年快乐","desc":"新年快乐","image":"/attire/20201224/a4c05d22333c5018ccb80b091786271e.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1608719935,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":171,"name":"草泥马","desc":"商城装扮","image":"/attire/20201223/314a2fac4abb9c41e9e9f5b0a3be98df.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201224/6dd14f1b9d3caa85fef130ec94f85df9.svga","color":"","createTime":1608720546,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":172,"name":"幸运锦鲤","desc":"活动专属","image":"/attire/20201223/04de538948ee3c36caaa902b10f49dc1.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201224/067edb7fee6c2e7d24ce9da2ae534f8c.svga","color":"","createTime":1608721051,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":173,"name":"七彩祥云","desc":"活动专属","image":"/attire/20201223/97f647fcfb08c9759d2f7e206a5321ad.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201223/212e097e37d7b040897c2205f23cbf11.svga","color":"","createTime":1608722064,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":174,"name":"神豪奖励-冰原狼王","desc":"周星装扮","image":"/attire/20201224/49cb92348de8caf3292f197bf82d317d.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201223/22ec03eb497e09478d2ca6319d51efae.svga","color":"","createTime":1608722201,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":175,"name":"牛气冲天","desc":"活动专属","image":"/attire/20201223/6b04cd0944a642b758ca5898542971cf.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201223/443cc262a482c4963663e6358cf05355.svga","color":"","createTime":1608722357,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":176,"name":"雪山冰狐","desc":"商城装扮","image":"/attire/20201223/27b0bf1e63f26c9dbb45d1af8e9bb0d7.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201223/2ed6ec64dd66fda151ca22bf8af0b82a.svga","color":"","createTime":1608722485,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":177,"name":"赤焰战狼","desc":"商城装扮","image":"/attire/20201224/3397f8dce6b0cadb370a64c7ecadb0e6.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201223/2ca09c612dd880bcedd56752104b2fce.svga","color":"","createTime":1608722566,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":178,"name":"梦幻马车","desc":"商城装扮","image":"/attire/20201223/a35fc054fcdbbce7518c8e05a4239d1a.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20201223/bfe6d651bda46a3c859040f21161e85c.svga","color":"","createTime":1608722676,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"mount","unit":{"type":"wearDay","displayName": "个"},"goodsId": 0},{"kindId":179,"name":"星耀30强","desc":"年度盛典","image":"/attire/20210202/7e41c3d3fcbf8d4f6aed3656f939aa8a.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210202/7dbd0c985ba964c179cc981248f9d8d9.svga","color":"","createTime":1611297192,"multiple":1.3,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":180,"name":"星耀10强","desc":"年度盛典","image":"/attire/20210202/9d372ac66422672d992b9032a2ddf898.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210202/0233a881e805754f750cb45d100069d5.svga","color":"","createTime":1612175000,"multiple":1.3,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":181,"name":"神豪10强","desc":"年度盛典","image":"/attire/20210202/b382e3288e8a34aa5df9101fd9198c89.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210202/b00185d40dceda89c3e1510bfb369372.svga","color":"","createTime":1612175184,"multiple":1.3,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":182,"name":"2020年度星耀冠军","desc":"年度盛典","image":"/attire/20210202/b2809860ff60ae2f03295ff2835b659b.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210201/862337181c8844c07f2cc99500770552.svga","color":"","createTime":1612176550,"multiple":1.3,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":183,"name":"2020年度星耀亚军","desc":"年度盛典","image":"/attire/20210202/acb01b98d9042b91505ebd5fd1751325.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210201/0fa423084557b507121a88bfe39e940c.svga","color":"","createTime":1612176669,"multiple":1.3,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":184,"name":"2020年度星耀季军","desc":"年度盛典","image":"/attire/20210202/823beb228f07a263ac0e544ff1e1aaa3.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210201/f9a4843ec0dc0613ba99c79ec3aae1a4.svga","color":"","createTime":1612176732,"multiple":1.3,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":185,"name":"2020年度神豪冠军","desc":"年度盛典","image":"/attire/20210202/d0ace3cbd4984b4401346e700ced3e32.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210201/e543ee13c3286b823df18c761532c296.svga","color":"","createTime":1612176817,"multiple":1.5,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":186,"name":"2020年度神豪亚军","desc":"年度盛典","image":"/attire/20210202/dca05578931e76a35474ba41bf36500e.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210201/584c5b91dc95b39ca9c5484835085dcf.svga","color":"","createTime":1612176879,"multiple":1.5,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":187,"name":"2020年度神豪季军","desc":"年度盛典","image":"/attire/20210202/1620a2260d8b6447fe13f782fb1c457e.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210201/dc4326c3c36f2b821d1a1f1058132f57.svga","color":"","createTime":1612176924,"multiple":1.4,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":188,"name":"周星专属头像框","desc":"周星装扮","image":"/attire/20210207/5f7e4171b8ea4100ac82fc4a83320a4c.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/2b89985f3f92456e11c6e7baefc522c5.svga","color":"","createTime":1612690439,"multiple":1.4,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":189,"name":"周星神豪头像框","desc":"周星装扮","image":"/attire/20210207/584aa3c6ece90c3b62c8de66596a3e7d.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/47a907c16b223df6fb5155d6a6a7fc2e.svga","color":"","createTime":1612690898,"multiple":1.4,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":190,"name":"星耀专属头像框","desc":"周星装扮","image":"/attire/20210207/90bc7c96afbdc04a0fa395fe58224545.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/beb09a0057f100c11319e55883f8121d.svga","color":"","createTime":1612690946,"multiple":1.4,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":191,"name":"天生一对钻石头像框（男）","desc":"情人节活动","image":"/attire/20210207/47d8c2922dd810efec2adafd6748a1d0.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/cab8705691a418ac23d818b235acac2a.svga","color":"","createTime":1612691732,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":192,"name":"天生一对钻石头像框（女）","desc":"情人节活动","image":"/attire/20210207/8988487bc4b6606d99179e127d642b59.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/b2529913949146be308db851f84cfd8d.svga","color":"","createTime":1612691847,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":193,"name":"天作之合翡翠头像框（男）","desc":"情人节活动","image":"/attire/20210207/a08d3a980bc113e012fe83b856473525.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/911621580f3b21bb74f75112c612d006.svga","color":"","createTime":1612691960,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":194,"name":"天作之合翡翠头像框（女）","desc":"情人节活动","image":"/attire/20210207/728e1f5689c0f5fa16d8ff6d18e9e6ef.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/9fdc6dd396c34ece76713088656dc915.svga","color":"","createTime":1612692028,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":195,"name":"缘定今生宝石头像框（男）","desc":"情人节活动","image":"/attire/20210207/b3328de8d2c1a052872d5e16dcdc99c4.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/0d43bd92aa40d9ab88b934f1f45b595f.svga","color":"","createTime":1612692096,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":196,"name":"缘定今生宝石头像框（女）","desc":"情人节活动","image":"/attire/20210207/86723b356c6e47d6bf1ebdec91413519.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/2f2044e42de5b966c511c6a8b53e4c8f.svga","color":"","createTime":1612692145,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":197,"name":"心心相印珍珠头像框（男）","desc":"情人节活动","image":"/attire/20210207/20a25ce81c7282f3db371a424955e901.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/d28d51f8014472a50f55d1f64a4f838e.svga","color":"","createTime":1612692236,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":198,"name":"心心相印珍珠头像框（女）","desc":"情人节活动","image":"/attire/20210207/c88c7d1f8f0111e617c8491d8d72efe1.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210207/642705dce0ad5168b8e68d626e1170e8.svga","color":"","createTime":1612692294,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":199,"name":"回归之星","desc":"回归用户专属","image":"/attire/20210222/48a8a3891891c14198409997d45b3ea6.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210222/bd47ccb2c35c89f77224f94cc7c54a88.svga","color":"","createTime":1613989731,"multiple":2,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":200,"name":"金牌主持人","desc":"金牌主持专属","image":"/attire/20210225/7d9245b66495a327d9fb532d025c140b.png","imageAndroid":"","bubbleWordImage":"","animation":"/attire/20210225/d63b95f468397da803229c7f6af769b1.svga","color":"","createTime":1614257413,"multiple":1.7,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":1,"type":"avatar","unit":{"type":"day","displayName": "个"},"goodsId": 0},{"kindId":201,"name":"许愿石","desc":"许愿石用来开启莫提斯宝箱","image":"/useravatar/20210316/ac74a080a1472750fbdc641b1864c4e4.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1614257413,"multiple":0,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":0,"type":"simple","unit":{"type":"count","displayName": "个"},"goodsId": 0},{"kindId":202,"name":"超级许愿石","desc":"超级许愿石用来开启宙斯宝箱","image":"/useravatar/20210317/4bc5537eb838dadbe3006a9d6ac15558.png","imageAndroid":"","bubbleWordImage":"","animation":"","color":"","createTime":1614257413,"multiple":0,"updateTime":0,"removeFormBagWhenDied":0,"showInBag":0,"type":"simple","unit":{"type":"count","displayName":"个"}}]
str;

    public static $EMOTICON_CONF = '{
        "emoticons":[
            {
                "id":601,
                "name":"害羞",
                "image":"/upload/20190523/c328a4d2641bbcbbf8c95b374e78e9b6.png",
                "vipLevel":0,
                "type": 1,
                "isLock": 2,
                "animation":"/upload/20190523/59fa6e70edc0ce65910c91cf299a7999.gif",
                "gameImages":[]
            },
            {
                "id":604,
                "name":"送花",
                "image":"/images/emoticon/rose.png",
                "vipLevel":0,
                "type": 1,
                "isLock": 2,
                "animation":"/images/emoticon/rose.gif",
                "gameImages":[]
            },
            {
                "id":702,
                "name":"麦序机",
                "image":"/upload/images/game3/1.png",
                "vipLevel":0,
                "type": 2,
                "isLock": 0,
                "animation":"/upload/images/game3/3.gif",
                "gameImages": [
                    "upload/images/game3/0.png", 
                    "upload/images/game3/1.png", 
                    "upload/images/game3/2.png", 
                    "upload/images/game3/3.png", 
                    "upload/images/game3/4.png", 
                    "upload/images/game3/5.png", 
                    "upload/images/game3/6.png", 
                    "upload/images/game3/7.png", 
                    "upload/images/game3/8.png"
                ]
            },
            {
                "id":605,
                "name":"发呆",
                "image":"/upload/20190523/1b35c4361bbd1dd990113d705cd50b7f.png",
                "vipLevel":0,
                "type": 1,
                "isLock": 2,
                "animation":"/upload/20190523/4a0ebc0726a786b4d164506eed6562f1.gif",
                "gameImages":[]
            },
            {
                "id":703,
                "name":"大哭",
                "image":"/banner/20200806/1cffdb88aeee271d81c0f224817d4cc1.png",
                "vipLevel":2,
                "type": 1,
                "isLock": 0,
                "animation":"/useravatar/20200806/926b822512ac6ff183e881cf1cef8bf4.gif",
                "gameImages":[]
            },
            {
                "id":706,
                "name":"告辞",
                "image":"/attire/20200921/e52745b6633d96bee8c5666063b181c7.png",
                "vipLevel":2,
                "type": 1,
                "isLock": 0,
                "animation":"/gift/20200923/f67671e5d636a073b1b4a385f390e30c.gif",
                "gameImages":[]
            }
        ]
    }';

    public static $EMOTICON_PANELS_CONF = '{
        "panels":[
            {
                "name":"normal",
                "icon":"",
                "mold":1,
                "emoticons":[601,604, 605, 702]
            },
            {
                "name":"special",
                "icon":"",
                "mold":2,
                "emoticons":[703,706]
            }
        ]
    }
    ';


    public static $DUKE_CONF = '
        {
            "levels":[
                {
                    "level":1,
                    "name":"游侠",
                    "picture":"/attire/20201211/45114d3769957c2820e6860a43783880.gif",
                    "value":6000,
                    "relegation":3600,
                    "animation":"/attire/20201214/9694fe3d7f71398a00df23ed5d19124a.svga",
                    "upgradeBroadcast":0,
                    "avoidKick":0,
                    "avoidForbidwords":0,
                    "privilegeDesc":[
                        {
                            "title":"专属勋章",
                            "pic":"/privilege/duke/xunzhang.png"
                        },
                        {
                            "title":"气泡框",
                            "pic":"/privilege/duke/qipaokuang.png"
                        }
                    ],
                    "privilegeAssets":[
                        {
                            "assetId":"prop:152",
                            "count":1
                        }
                    ]
                },
                {
                    "level":2,
                    "name":"骑士",
                    "picture":"/attire/20201211/6b42c0d92d13c9a36b802fa7d0d33449.gif",
                    "value":30000,
                    "relegation":18000,
                    "animation":"/attire/20201214/cb3550aab6672f7be51351f659d6d658.svga",
                    "upgradeBroadcast":0,
                    "avoidKick":0,
                    "avoidForbidwords":0,
                    "privilegeDesc":[
                        {
                            "title":"头像框",
                            "pic":"/privilege/duke/touxiangkuang.png"
                        }
                    ],
                    "privilegeAssets":[
                        {
                            "assetId":"prop:151",
                            "count":1
                        },
                        {
                            "assetId":"prop:164",
                            "count":1
                        }
                    ]
                },
                {
                    "level":3,
                    "name":"伯爵",
                    "picture":"/attire/20201211/3adbec40532d3a90086e4065c1db0994.gif",
                    "value":100000,
                    "relegation":60000,
                    "animation":"/attire/20201214/5144d77938345a1427dda0267986df4f.svga",
                    "upgradeBroadcast":0,
                    "avoidKick":0,
                    "avoidForbidwords":0,
                    "privilegeDesc":[
                        {
                            "title":"特权礼物",
                            "pic":"/privilege/duke/tequanliwu.png"
                        },
                        {
                            "title":"麦位光圈",
                            "pic":"/privilege/duke/maiwei.png"
                        }
                    ],
                    "privilegeAssets":[
                        {
                            "assetId":"prop:158",
                            "count":1
                        },
                        {
                            "assetId":"prop:160",
                            "count":1
                        },
                        {
                            "assetId":"prop:154",
                            "count":1
                        }
                    ]
                },
                {
                    "level":4,
                    "name":"公爵",
                    "picture":"/attire/20201211/223d4870965edb33d37d9e8263e9249d.gif",
                    "value":300000,
                    "relegation":180000,
                    "animation":"/attire/20201214/451f9e16b91e6359016769f60be11aa6.svga",
                    "upgradeBroadcast":0,
                    "avoidKick":0,
                    "avoidForbidwords":0,
                    "privilegeDesc":[
                        {
                            "title":"座驾",
                            "pic":"/privilege/duke/zuojia.png"
                        },
                        {
                            "title":"升级广播",
                            "pic":"/privilege/duke/guangbo.png"
                        }
                    ],
                    "privilegeAssets":[
                        {
                            "assetId":"prop:153",
                            "count":1
                        },
                        {
                            "assetId":"prop:150",
                            "count":1
                        },
                        {
                            "assetId":"prop:148",
                            "count":1
                        },
                        {
                            "assetId":"prop:155",
                            "count":1
                        }
                    ]
                },
                {
                    "level":5,
                    "name":"国王",
                    "picture":"/attire/20201211/c9fc1df6265d1dd29442e2f064fee8d8.gif",
                    "value":800000,
                    "relegation":480000,
                    "animation":"/attire/20201214/97f12309421bcd3728a9860ba91a0731.svga",
                    "upgradeBroadcast":0,
                    "avoidKick":0,
                    "avoidForbidwords":0,
                    "privilegeDesc":[
                        {
                            "title":"防禁言",
                            "pic":"/privilege/duke/fangjinyan.png"
                        },
                        {
                            "title":"防踢",
                            "pic":"/privilege/duke/fangti.png"
                        },
                        {
                            "title":"专属管家",
                            "pic":"/privilege/duke/guanjia.png"
                        }
                    ],
                    "privilegeAssets":[
                        {
                            "assetId":"prop:161",
                            "count":1
                        },
                        {
                            "assetId":"prop:149",
                            "count":1
                        },
                        {
                            "assetId":"prop:157",
                            "count":1
                        },
                        {
                            "assetId":"prop:156",
                            "count":1
                        }
                    ]
                }
            ]
        }
    ';

    public static $CHARGE_CONF = '{
        "products":[
            {
                "productId":101,
                "price":6.00,
                "bean":42,
                "present":60,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.6",
                "chargeMsg":"充了一小笔寻币，想找人聊聊。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":42}
                ]
            },
            {
                "productId":102,
                "price":30.00,
                "bean":210,
                "present":210,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.m.30",
                "chargeMsg":"没什么好谈的，只想和你谈恋爱。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":210}
                ]
            },
            {
                "productId":103,
                "price":68.00,
                "bean":476,
                "present":476,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.m.68",
                "chargeMsg":"就是这么任性，不服来辩。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":476}
                ]
            },
            {
                "productId":104,
                "price":6.00,
                "bean":0,
                "present":60,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.0",
                "chargeMsg":"就是这么任性，不服来辩。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":30},
                    {"assetId":"prop:5", "count":7},
                    {"assetId":"prop:201", "count":1},
                    {"assetId":"gift:360", "count":18},
                    {"assetId":"gift:368", "count":8},
                    {"assetId":"gift:251", "count":8}
                ]
            },
            {
                "productId":105,
                "price":40.00,
                "bean":0,
                "present":60,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.vip.40",
                "chargeMsg":"",
                "deliveryItems":[
                    {"assetId":"user:vip_month", "count":1}
                ]
            },
            {
                "productId":106,
                "price":88.00,
                "bean":0,
                "present":60,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.vip.88",
                "chargeMsg":"",
                "deliveryItems":[
                    {"assetId":"user:vip_month", "count":3}
                ]
            },
            {
                "productId":107,
                "price":168.00,
                "bean":0,
                "present":60,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.vip.168",
                "chargeMsg":"",
                "deliveryItems":[
                    {"assetId":"user:vip_month", "count":6}
                ]
            },
            {
                "productId":108,
                "price":288.00,
                "bean":0,
                "present":60,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.vip.288",
                "chargeMsg":"",
                "deliveryItems":[
                    {"assetId":"user:svip_month", "count":12}
                ]
            },
            {
                "productId":109,
                "price":30.00,
                "bean":210,
                "present":30,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.30",
                "chargeMsg":"充了一小笔寻币，想找人聊聊。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":210}
                ]
            },
            {
                "productId":110,
                "price":208.00,
                "bean":1456,
                "present":208,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.208",
                "chargeMsg":"充了一小笔寻币，想找人聊聊。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":1456}
                ]
            },
            {
                "productId":111,
                "price":1408.00,
                "bean":4536,
                "present":1408,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "appStoreProductId":"com.party.fq.1408",
                "chargeMsg":"充了一小笔寻币，想找人聊聊。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":4536}
                ]
            },
            {
                "productId":201,
                "price":10.00,
                "bean":100,
                "present":100,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":100}
                ]
            },
            {
                "productId":202,
                "price":30.00,
                "bean":300,
                "present":30,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":300}
                ]
            },
            {
                "productId":203,
                "price":50.00,
                "bean":500,
                "present":50,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":500}
                ]
            },
            {
                "productId":204,
                "price":100.00,
                "bean":1000,
                "present":396,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":1000}
                ]
            },
            {
                "productId":205,
                "price":200.00,
                "bean":2000,
                "present":656,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":2000}
                ]
            },
            {
                "productId":206,
                "price":500.00,
                "bean":5000,
                "present":1296,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":5000}
                ]
            },
            {
                "productId":207,
                "price":1000.00,
                "bean":10000,
                "present":1000,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":10000}
                ]
            },
            {
                "productId":208,
                "price":5000.00,
                "bean":50000,
                "present":1996,
                "image":"/Public/Uploads/img/123.png",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":50000}
                ]
            },
            {
                "productId":209,
                "price":6.00,
                "bean":0,
                "present":60,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "chargeMsg":"就是这么任性，不服来辩。",
                "deliveryItems":[
                    {"assetId":"user:bean", "count":30},
                    {"assetId":"prop:5", "count":7},
                    {"assetId":"prop:201", "count":1},
                    {"assetId":"gift:360", "count":18},
                    {"assetId":"gift:368", "count":8},
                    {"assetId":"gift:251", "count":8}
                ]
            },
            {
                "productId":210,
                "price":36.00,
                "bean":0,
                "present":36,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "chargeMsg":"就是这么任性，不服来辩。",
                "deliveryItems":[
                    {"assetId":"user:vip_month", "count":1}
                ]
            },
            {
                "productId":211,
                "price":88.00,
                "bean":0,
                "present":100,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "chargeMsg":"就是这么任性，不服来辩。",
                "deliveryItems":[
                    {"assetId":"user:vip_month", "count":3}
                ]
            },
            {
                "productId":212,
                "price":166.00,
                "bean":0,
                "present":216,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "chargeMsg":"就是这么任性，不服来辩。",
                "deliveryItems":[
                    {"assetId":"user:vip_month", "count":3}
                ]
            },
            {
                "productId":213,
                "price":288.00,
                "bean":0,
                "present":432,
                "image":"/Public/Uploads/img/123.png",
                "status":0,
                "chargeMsg":"就是这么任性，不服来辩。",
                "deliveryItems":[
                    {"assetId":"user:svip_month", "count":12}
                ]
            }
        ]
    }';

    public static $CHARGE_MALL = '{
        "ios":{
            "products":[101,102,103],
            "vip":[105,106,107,108],
            "svip":[108],
            "firstPay":[104],
            "redPacket":[109,110,111]
        },
        "android":{
            "products":[201,202,203,204,205,206,207,208],
            "vip":[210,211,212,213],
            "svip":[213],
            "firstPay":[209],
            "redPacket":[202,205,207]
        }
    }';

    public static $RED_PACKET = '{
        "timesConf":[
            {"seconds":30, "display":"30s"},
            {"seconds":60, "display":"1min"},
            {"seconds":180, "display":"3min"},
            {"seconds":300, "display":"5min"}
        ],
        "times":[
            ["30s","1min"],
            ["30s","1min","3min"],
            ["30s","1min","3min","5min"]
        ],
        "areas":{
            "android":[
                {
                    "value":300,
                    "productId":202,
                    "showType":1
                },
                {
                    "value":2000,
                    "productId":205,
                    "showType":2
                },
                {
                    "value":10000,
                    "productId":207,
                    "showType":3
                }
            ],
            "ios":[
                {
                    "value":210,
                    "productId":109,
                    "showType":1
                },
                {
                    "value":1456,
                    "productId":110,
                    "showType":2
                },
                {
                    "value":4536,
                    "productId":111,
                    "showType":3
                }
            ]
        }
    }';

    protected static $instance;
    protected $redis = null;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
            self::$instance->redis = RedisCommon::getInstance()->getRedis(["select" => 3]);
        }
        return self::$instance;
    }

    private function dealSourceConfigKey()
    {
        return [
            'qingqing_charge_conf',
            'qingqing_chargemall_conf',
            'qingqing_vip_conf',
            'qingqing_duke_conf',
        ];
    }

    /**
     * @param $key
     * @return array|mixed
     */
    public function getConfigByKey($key){
        $source = app('request')->header('source', '');
        $configKey = sprintf('%s_%s', $source, $key);
        if (in_array($configKey, $this->dealSourceConfigKey())) {
            $key = $configKey;
        }
        $conf = $this->redis->get($key);
        if (empty($conf)){
            Log::error(sprintf('Config Exception not config=%s', $key));
            return array();
        }
        return json_decode($conf, true);
    }

    public function setConfigByKey($key, $conf) {
        $jstr = json_encode($conf);
        $this->redis->set($key, $jstr);
    }

    public function getEmoticonConf() {
        $emoticonConf = $this->getConfigByKey("emoticon_conf");
        if(!ArrayUtil::safeGet($emoticonConf, 'emoticons')){
            return ['emoticons' => $emoticonConf];
        }

        return $emoticonConf;
    }

    public function getGiftConf() {
        $giftConf = $this->getConfigByKey("gift_conf");

        if(!ArrayUtil::safeGet($giftConf, 'gifts')){
            return ['gifts' => $giftConf];
        }

        return $giftConf;
    }

    public function getCityConf() {
        return $this->getConfigByKey("city_conf");
    }

    public function getWeekStarConf() {
        return $this->getConfigByKey("week_star_conf");
    }

    public function getPropConf() {
        $propConf = $this->getConfigByKey("prop_conf");
//        $propConf=$this->getConfigByKeyPropTest();

        if(!ArrayUtil::safeGet($propConf, 'props')){
            return ['props' => $propConf];
        }

        return $propConf;
    }


    private function getConfigByKeyPropTest(){
        $conf=$this->PROP_CONF;
        if (empty($conf)){
            Log::error(sprintf('Config Exception not config=%s', "prop_conf"));
        }
        return json_decode($conf, true);
    }

    public function getGoodsConfig() {
        $goodsConf= $this->getConfigByKey("goods_conf");
        if(!ArrayUtil::safeGet($goodsConf, 'goods')){
            return ['goods' => $goodsConf];
        }

        return $goodsConf;
    }

    public function getMallConfig() {
        return $this->getConfigByKey("mall_conf");
    }

    public function getMall2Config() {
        return $this->getConfigByKey("mall2_conf");
    }

    public function getPanelsConf($panelsConfKey) {
        return $this->getConfigByKey($panelsConfKey);
    }

    public function getWallsConf() {
        return $this->getConfigByKey("gift_wall");
    }

    public function getGiftWallConf() {
        return $this->getConfigByKey("gift_wall_conf");
    }

    public function getGiftCollectionConf() {
        return $this->getConfigByKey("gift_collection_conf");
    }

    public function getEmoticonPanelsConf() {
        $conf = $this->getConfigByKey("emoticon_panels_conf");
        if(!ArrayUtil::safeGet($conf, 'panels')){
            return ['panels' => $conf];
        }

        return $conf;
    }

    public function getVipConf() {
        return $this->getConfigByKey("vip_conf");
    }

    public function getVipConfLite() {
        return $this->getConfigByKey("vip_conf_lite");
    }

    public function getDukeConf() {
        return $this->getConfigByKey("duke_conf");
    }

    public function getChargeConf() {
//        return json_decode(self::$CHARGE_CONF, true);
        return $this->getConfigByKey("charge_conf");
    }

    public function getBeanCoinConf() {
//        return json_decode(self::$CHARGE_CONF, true);
        return $this->getConfigByKey("bean_coin_conf");
    }

    public function getChargeMallConf() {
//        return json_decode(self::$CHARGE_MALL, true);
        return $this->getConfigByKey("chargemall_conf");
    }

    public function getWeekCheckInConf() {
        return $this->getConfigByKey("weekcheckin_conf");
    }

    public function getDailyConfig() {
        return $this->getConfigByKey("daily_conf");
    }

    public function getActiveBoxConfig() {
        return $this->getConfigByKey("activebox_conf");
    }

    public function getNewerConfig() {
        return $this->getConfigByKey("newer_conf");
    }

    public function getLotteryConf() {
        return $this->getConfigByKey("lottery_conf");
    }

    public function getBoxConf() {
        return $this->getConfigByKey("box_conf");
    }

    public function getLevelConf() {
        return $this->getConfigByKey("level_conf");
    }

    public function getRiskWarnConf() {
        return $this->getConfigByKey("risk_warning_conf");
    }

    public function getLedConf() {
        return $this->getConfigByKey("led_conf");
    }

    public function getLedJumpConf() {
        return $this->getConfigByKey("led_jump_conf");
    }

    public function getLevelConfLite() {
        return $this->getConfigByKey("level_conf_lite");
    }

    public function getTaoJibConf() {
        return $this->getConfigByKey("taojin_conf");
    }

    public function getRedPacketConf() {
        return json_decode(self::$RED_PACKET, true);
    }

    public function getBox2Conf() {
        return $this->getConfigByKey('box2_conf');
    }


    public function getRoomModeConf() {
        return $this->getConfigByKey('room_mode_conf');
    }

    public function getRoomTagConf() {
        return $this->getConfigByKey('room_tag_conf');
    }

    public function getChannelVersionConf() {
        return $this->getConfigByKey('channel_version_conf');
    }

    public function getBottomMenuConf() {
        return $this->getConfigByKey('bottom_menu_conf');
    }

    public function getFistChargeRewardConf() {
        return $this->getConfigByKey("first_charge_reward_conf");
    }

    public function getVersionCheckConf() {
        return $this->getConfigByKey('version_check_conf');
    }

    // IM表情包
    public function getImEmotionConf() {
        return $this->getConfigByKey("im_emotion_conf");
    }

    // IM背景图
    public function getImBackgroundConf() {
        return $this->getConfigByKey("im_background_conf");
    }
}