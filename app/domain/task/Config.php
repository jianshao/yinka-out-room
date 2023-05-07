<?php
namespace app\domain\task;
class Config{
    /*
     * 为了兼容之前的协议
     * id:
     * 1-100是签到
     * 100-200是每日任务
     * 200-300是新手任务
     * 300-400是活跃开宝箱
     * */
    public static $WEEKCHECKIN_CONFIG = '
        {
            "weekcheckin":[
                {
                    "id":1,
                    "name": "周一",
                    "desc": "签到任务",
                    "count":1,
                    "cycle": "week",
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":50,
                            "name": "金币",
                            "img": "/gold.png"
                        }
                    ]
                },
                {
                    "id":2,
                    "name": "周二",
                    "desc": "签到任务",
                    "count":2,
                    "cycle": "week",
                    "rewards":[
                        {
                            "assetId":"gift:360",
                            "count":1,
                            "name": "小星星",
                            "img": "/gift/20200814/dc2fb670b42d89f5beb79009ae62a0cf.png"
                        }
                    ]
                },
                {
                    "id":3,
                    "name": "周三",
                    "desc": "签到任务",
                    "count":3,      
                    "cycle": "week",
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":50,
                            "name": "金币",
                            "img": "/gold.png"
                        }
                    ]
                },
                {
                    "id":4,
                    "name": "周四",
                    "desc": "签到任务",
                    "count":4,
                    "cycle": "week",
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":50,
                            "name": "金币",
                            "img": "/gold.png"                 
                        }
                    ]
                },
                {
                    "id":5,
                    "name": "周五",
                    "desc": "签到任务",
                    "count":5,
                    "cycle": "week",
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":50,
                            "name": "金币",
                            "img": "/gold.png"
                        }
                    ]
                },
                {
                    "id":6,
                    "name": "周六",
                    "desc": "签到任务",
                    "count":6,
                    "cycle": "week",
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":50,
                            "name": "金币",
                            "img": "/gold.png"
                        }
                    ]
                },
                {
                    "id":7,
                    "name": "周日",
                    "desc": "签到任务",
                    "count":7,
                    "cycle": "week",
                    "rewards":[
                        {
                            "assetId":"gift:360",
                            "count":1,
                            "name": "小星星",
                            "img": "/gift/20200814/dc2fb670b42d89f5beb79009ae62a0cf.png"
                        }
                    ]
                }
            ]
        }
    ';

    public static $DAILY_CONFIG = '
        {
            "daily":[
                {
                    "id":101,
                    "name": "每日登录",
                    "desc": "每日任务",
                    "count":1,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.login",
                            "displayName":"每日登陆"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":50,
                            "name": "*50",
                            "img": "/image/coin.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":10,
                            "name": "+10",
                            "img": "/image/exp.png"
                        }
                    ]
                },
                {
                    "id":102,
                    "name": "关注一个房间",
                    "desc": "每日任务",
                    "count":1,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.focus.room",
                            "displayName":"关注一个房间"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":75,
                            "name": "*75",
                            "img": "/image/coin.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":15,
                            "name": "+15",
                            "img": "/image/exp.png"
                        }
                    ]
                },
                {
                    "id":103,
                    "name": "充值任意金额",
                    "desc": "每日任务",
                    "count":1,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.recharge",
                            "displayName":"充值任意金额"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"gift:360",
                            "count":2,
                            "name": "*2",
                            "img": "/gift/20200814/dc2fb670b42d89f5beb79009ae62a0cf.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":20,
                            "name": "+20",
                            "img": "/image/exp.png"
                        }
                    ]
                },
                {
                    "id":104,
                    "name": "送出任意番茄豆礼物",
                    "desc": "每日任务",
                    "count":1,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.send.bean",
                            "displayName":"送出任意番茄豆礼物"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"gift:360",
                            "count":1,
                            "name": "*1",
                            "img": "/gift/20200814/dc2fb670b42d89f5beb79009ae62a0cf.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":15,
                            "name": "+15",
                            "img": "/image/exp.png"
                        }
                    ]
                },
                {
                    "id":105,
                    "name": "私聊一个用户",
                    "desc": "每日任务",
                    "count":1,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.private.chat",
                            "displayName":"私聊一个用户"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":50,
                            "name": "*50",
                            "img": "/image/coin.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":10,
                            "name": "+10",
                            "img": "/image/exp.png"
                        }
                    ]
                },
                {
                    "id":106,
                    "name": "房间停留五分钟",
                    "desc": "每日任务",
                    "count": 300,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.stay.room.5m",
                            "displayName":"房间停留五分钟"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":150,
                            "name": "*150",
                            "img": "/image/coin.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":20,
                            "name": "+20",
                            "img": "/image/exp.png"
                        }
                    ]
                },
                {
                    "id":107,
                    "name": "发布一次动态",
                    "desc": "每日任务",
                    "count":1,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.release.dynamic",
                            "displayName":"发布一次动态"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":100,
                            "name": "*100",
                            "img": "/image/coin.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":15,
                            "name": "+15",
                            "img": "/image/exp.png"
                        }
                    ]
                },
                {
                    "id":108,
                    "name": "分享一次房间",
                    "desc": "每日任务",
                    "count":1,
                    "cycle": "day",
                    "inspectors": [
                        {
                            "type":"user.share.room",
                            "displayName":"分享一次房间"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":75,
                            "name": "*75",
                            "img": "/image/coin.png"
                        },
                        {
                            "assetId":"user:active_degree",
                            "count":15,
                            "name": "+15",
                            "img": "/image/exp.png"
                        }
                    ]
                }
            ]
        }
    ';

    public static $ACTIVEBOX_CONFIG = '
    {
      "activeinfo": [
        {
          "title": "活跃度说明",
          "content": "每日完成特定任务赋予活跃度。"
        },
        {
          "title": "日活跃度礼盒",
          "content": "每日达到对应活跃度可领取奖励，每人每天最多领取三次。日活跃度值每天零点会重置。每项奖励每天可领取一次，未领取不逐步到次日。"
        },
        {
          "title": "周活跃度礼盒",
          "content": "每周达到对应活跃度可领取奖励，每人每周最多领取两次。周活跃度值每周一零点会重置。每项奖励每周可领取一次，当周没有领取不逐步到下周。"
        }
      ],
      "activebox": [
        {
          "id": 301,
          "name": "30日活跃度开宝",
          "desc": "日常任务",
          "count": 30,
          "cycle": "day",
          "rewards": [
            {
              "assetId": "user:coin",
              "count": 50,
              "name": "金币",
              "img": "/image/coin.png"
            }
          ]
        },
        {
          "id": 302,
          "name": "70日活跃度开宝",
          "desc": "日常任务",
          "count": 70,
          "cycle": "day",
          "rewards": [
            {
              "assetId": "user:coin",
              "count": 200,
              "name": "金币",
              "img": "/image/coin.png"
            }
          ]
        },
        {
          "id": 303,
          "name": "100日活跃度开宝",
          "desc": "日常任务",
          "count": 100,
          "cycle": "day",
          "rewards": [
            {
              "assetId": "gift:251",
              "count": 1,
              "name": "比心",
              "img": "/gift/20200711/61d78c5de0ae96181fc1db2349d19dee.png"
            }
          ]
        },
        {
          "id": 304,
          "name": "300周活跃度开宝",
          "desc": "日常任务",
          "count": 300,
          "cycle": "week",
          "rewards": [
            {
              "type": "RandomContent",
              "randoms": [
                {
                  "weight": 10,
                  "assetId": "gift:250",
                  "count": 1,
                  "name": "棒棒糖",
                  "img": "/gift/20200711/693cefee016f3285874d908459577dd0.png"
                },
                {
                  "weight": 30,
                  "assetId": "gift:251",
                  "count": 1,
                  "name": "比心",
                  "img": "/gift/20200711/61d78c5de0ae96181fc1db2349d19dee.png"
                },
                {
                  "weight": 60,
                  "assetId": "gift:368",
                  "count": 1,
                  "name": "试音卡",
                  "img": "/gift/20200730/7117d7e508776098e2b7170983b38c36.png"
                }
              ]
            }
          ]
        },
        {
          "id": 305,
          "name": "600周活跃度开宝",
          "desc": "日常任务",
          "count": 600,
          "cycle": "week",
          "rewards": [
            {
              "type": "RandomContent",
              "randoms": [
                {
                  "weight": 40,
                  "assetId": "gift:250",
                  "count": 1,
                  "name": "棒棒糖",
                  "img": "/gift/20200711/693cefee016f3285874d908459577dd0.png"
                },
                {
                  "weight": 50,
                  "assetId": "gift:251",
                  "count": 1,
                  "name": "比心",
                  "img": "/gift/20200711/61d78c5de0ae96181fc1db2349d19dee.png"
                },
                {
                  "weight": 10,
                  "assetId": "gift:343",
                  "count": 1,
                  "name": "仙女棒",
                  "img": "/gift/20200711/0d395170521cce3fdd8fad7f22683d2f.png"
                }
              ]
            }
          ]
        }
      ]
    }
    ';

    public static $NEWER_CONFIG = '
        {
            "newer":[
                {
                    "id":201,
                    "name": "完善个人资料(%d/%d)",
                    "desc": "新手任务",
                    "count":6,
                    "inspectors": [
                        {
                            "type":"user.complete.info",
                            "displayName":"完善个人资料"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":100,
                            "name": "*100",
                            "img": "/image/coin.png"
                        }
                    ]
                },
                {
                    "id":202,
                    "name": "关注一个房间",
                    "desc": "新手任务",
                    "count":1,
                    "inspectors": [
                        {
                            "type":"user.focus.room",
                            "displayName":"关注一个房间"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":100,
                            "name": "*100",
                            "img": "/image/coin.png"
                        }
                    ]
                },
                {
                    "id":203,
                    "name": "绑定手机号",
                    "desc": "新手任务",
                    "count":1,
                    "inspectors": [
                        {
                            "type":"user.bind.mobile",
                            "displayName":"绑定手机号"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":150,
                            "name": "*150",
                            "img": "/image/coin.png"
                        }
                    ]
                },
                {
                    "id":204,
                    "name": " 完成实名认证",
                    "desc": "新手任务",
                    "count":1,
                    "inspectors": [
                        {
                            "type":"user.complete.realuser",
                            "displayName":"完成实名认证"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":100,
                            "name": "*100",
                            "img": "/image/coin.png"
                        },
                        {
                            "assetId":"gift:251",
                            "count":1,
                            "name": "*1",
                            "img": "/gift/20200711/61d78c5de0ae96181fc1db2349d19dee.png"
                        }
                    ]
                },
                {
                    "id":205,
                    "name": "连续登陆三天",
                    "desc": "新手任务",
                    "count":3,
                    "inspectors": [
                        {
                            "type":"user.login",
                            "displayName":"连续登陆三天"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":100,
                            "name": "*100",
                            "img": "/image/coin.png"
                        }
                    ]
                },
                {
                    "id":206,
                    "name": "开一次魔盒",
                    "desc": "新手任务",
                    "count":1,
                    "inspectors": [
                        {
                            "type":"user.open.magicbox",
                            "displayName":"开一次魔盒"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"gift:251",
                            "count":1,
                            "name": "*1",
                            "img": "/gift/20200711/61d78c5de0ae96181fc1db2349d19dee.png"
                        }
                    ]
                },
                {
                    "id":207,
                    "name": "创建一次房间",
                    "desc": "新手任务",
                    "count":1,
                    "inspectors": [
                        {
                            "type":"user.create.room",
                            "displayName":"创建一次房间"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":200,
                            "name": "*200",
                            "img": "/image/coin.png"
                        }
                    ]
                },
                {
                    "id":208,
                    "name": "关注一位好友",
                    "desc": "新手任务",
                    "count":1,
                    "inspectors": [
                        {
                            "type":"user.focus.friend",
                            "displayName":"关注一位好友"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"user:coin",
                            "count":100,
                            "name": "*100",
                            "img": "/image/coin.png"
                        }
                    ]
                },
                {
                    "id":209,
                    "name": "完成所有新手任务",
                    "desc": "新手任务",
                    "count":7,
                    "inspectors": [
                        {
                            "type":"user.complete.newer.task",
                            "displayName":"完成所有新手任务"
                        }
                    ],
                    "rewards":[
                        {
                            "assetId":"gift:250",
                            "count":1,
                            "name": "*1",
                            "img": "/gift/20200711/693cefee016f3285874d908459577dd0.png"
                        }
                    ]
                }
            ]
        }
    ';

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function getWeekCheckInConf() {
        return json_decode(self::$WEEKCHECKIN_CONFIG, true);
    }

    public function getDailyConfig() {
        return json_decode(self::$DAILY_CONFIG, true);
    }

    public function getActiveBoxConfig() {
        return json_decode(self::$ACTIVEBOX_CONFIG, true);
    }

    public function getNewerConfig() {
        return json_decode(self::$NEWER_CONFIG, true);
    }
}
