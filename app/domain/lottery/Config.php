<?php
namespace app\domain\lottery;

class Config{
    /*
     * */
    public static $CONFIG = '
    {
     "coinLottery": {
      "rules": [
       "1、抽奖一次消耗200金币，连抽五次需要1000金币",
       "2、抽奖获得的头像框、礼物等奖励会放入您的背包中，您可在背包内查看奖品",
       "3、抽奖获得的金币直接存入您的金币账户中",
       "4、平台会根据活动情况调整金币抽好礼活动的规则及奖品清单;",
       "5、用户在活动期间通过作弊或其他非正常手段获得奖品，平台有权收回，同时依据相关规则对其进行处罚"
      ],
      "priceList": [
       {
        "num": 1,
        "price": {
         "assetId": "user:coin",
         "count": 200
        }
       },
       {
        "num": 5,
        "price": {
         "assetId": "user:coin",
         "count": 200
        }
       }
      ],
      "lotterys": [
       {
        "id": 1,
        "weight": 5,
        "name": "梦幻之夜",
        "img": "/attire/20200903/579f3b016279b65704db77bc53f18e88.png",
        "reward": {
         "assetId": "prop:129",
         "count": 7
        }
       },
       {
        "id": 2,
        "weight": 5,
        "name": "试音卡",
        "img": "/gift/20200730/7117d7e508776098e2b7170983b38c36.png",
        "reward": {
          "assetId": "gift:368",
          "count": 1
        }
       },
       {
        "id": 3,
        "weight": 5,
        "name": "金币",
        "img": "/gold.png",
        "reward": {
         "assetId": "user:coin",
         "count": 888
        }
       },
       {
        "id": 4,
        "weight": 50,
        "name": "金币",
        "img": "/gold.png",
        "reward": {
         "assetId": "user:coin",
         "count": 188
         }
        },
       {
        "id": 5,
        "weight": 5,
        "name": "比心",
        "img": "/gift/20200711/61d78c5de0ae96181fc1db2349d19dee.png",
        "reward": {
         "assetId": "gift:246",
         "count": 1
        }
       },
       {
        "id": 6,
        "weight": 1,
        "name": "棒棒糖",
        "img": "/gift/20200711/693cefee016f3285874d908459577dd0.png",
        "reward": {
         "assetId": "gift:250",
         "count": 1
        }
       },
       {
        "id": 7,
        "weight": 20,
        "assetId": "user:coin",
        "count": 288,
        "name": "金币",
        "img": "/gold.png",
        "reward": {
         "assetId": "user:coin",
         "count": 288
        }
       },
       {
        "id": 8,
        "weight": 20,
        "name": "小星星",
        "img": "/gift/20200814/dc2fb670b42d89f5beb79009ae62a0cf.png",
        "reward": {
         "assetId": "gift:360",
         "count": 1
        }
       }
      ]
     }
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

    public function getLotteryConf() {
        return json_decode(self::$CONFIG, true);
    }
}
