<?php
namespace app\domain\game\taojin;
class Config{

    public static $CONFIG = '
    {
      "games": [
        {
          "gameId": 1,
          "name": "沙之城",
          "image": "/game/20201107/a187f4fde97c98d16fa4a6b03108cba6.png",
          "energy": 20,
          "status": 1,
          "bgmap": "/game/20201107/79a7902b331bb3391ce0c9951e5f47c3.jpg",
          "cover": "/game/20201109/dbc98d0a21a564c6eca08faf8c3d13c4.jpg",
          "map": "/game/20201107/e1ac067b886866acdf060c6266cd062a.png",
          "covermap": "/game/20201107/0e30f6955fa93aab86f1c77e44c4e1b2.png",
          "diceReward": [
          {"reward": {"assetId": "user:bean", "count": 10}, "id": 1, "weight": 10800}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 2, "weight": 900}, 
          {"reward": {"assetId": "user:bean", "count": 1}, "id": 3, "weight": 10800}, 
          {"reward": {"assetId": "ore:silver", "count": 1}, "id": 4, "weight": 50}, 
          {"reward": {"assetId": "user:bean", "count": 500}, "id": 5, "weight": 300}, 
          {"reward": {"assetId": "user:bean", "count": 5}, "id": 6, "weight": 10800}, 
          {"reward": {"assetId": "user:bean", "count": 20}, "id": 7, "weight": 500}, 
          {"reward": {"assetId": "user:bean", "count": 1000}, "id": 8, "weight": 100}, 
          {"reward": {"assetId": "user:bean", "count": 10}, "id": 9, "weight": 10800}, 
          {"reward": {"assetId": "user:bean", "count": 10}, "id": 10, "weight": 10800}, 
          {"reward": {"assetId": "ore:iron", "count": 1}, "id": 11, "weight": 100}, 
          {"reward": {"assetId": "user:bean", "count": 50}, "id": 12, "weight": 500}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 13, "weight": 900}, 
          {"reward": {"assetId": "user:bean", "count": 2000}, "id": 14, "weight": 50}, 
          {"reward": {"assetId": "user:bean", "count": 10}, "id": 15, "weight": 10800}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 16, "weight": 900}, 
          {"reward": {"assetId": "user:bean", "count": 50}, "id": 17, "weight": 1000}, 
          {"reward": {"assetId": "user:bean", "count": 1}, "id": 18, "weight": 10800}, 
          {"reward": {"assetId": "user:bean", "count": 10000}, "id": 19, "weight": 1}, 
          {"reward": {"assetId": "user:bean", "count": 20}, "id": 20, "weight": 500}
          ]
        },
        {
          "gameId": 2,
          "name": "海之城",
          "image": "/game/20201107/339630995ad47261766a5a375552be81.png",
          "energy": 50,
          "status": 1,
          "bgmap": "/game/20201107/7840f874bdf99a4e61c25b38c8009220.jpg",
          "cover": "/game/20201106/676cedafabd5b475adb011a0498c527d.png",
          "map": "/game/20201107/58e8c8241754c9598836a5adab25bdbf.png",
          "covermap": "/game/20201109/bc87a51e2e0e6016f771c0a1ceb97494.png",
          "diceReward":[
            {"reward": {"assetId": "user:bean", "count": 500}, "id": 1, "weight": 1600}, 
            {"reward": {"assetId": "user:bean", "count": 20}, "id": 2, "weight": 25000}, 
            {"reward": {"assetId": "user:bean", "count": 5000}, "id": 3, "weight": 240}, 
            {"reward": {"assetId": "user:bean", "count": 100}, "id": 4, "weight": 5000}, 
            {"reward": {"assetId": "user:bean", "count": 50}, "id": 5, "weight": 15000}, 
            {"reward": {"assetId": "user:bean", "count": 10000}, "id": 6, "weight": 10}, 
            {"reward": {"assetId": "user:bean", "count": 50}, "id": 7, "weight": 10000}, 
            {"reward": {"assetId": "user:bean", "count": 500}, "id": 8, "weight": 3500}, 
            {"reward": {"assetId": "user:bean", "count": 2000}, "id": 9, "weight": 580}, 
            {"reward": {"assetId": "user:bean", "count": 100}, "id": 10, "weight": 6000}, 
            {"reward": {"assetId": "user:bean", "count": 20}, "id": 11, "weight": 30000}, 
            {"reward": {"assetId": "ore:silver", "count": 1}, "id": 12, "weight": 300}, 
            {"reward": {"assetId": "user:bean", "count": 50}, "id": 13, "weight": 13000}, 
            {"reward": {"assetId": "ore:gold", "count": 1}, "id": 14, "weight": 100}, 
            {"reward": {"assetId": "user:bean", "count": 20}, "id": 15, "weight": 25000}, 
            {"reward": {"assetId": "user:bean", "count": 100}, "id": 16, "weight": 5000}, 
            {"reward": {"assetId": "user:bean", "count": 1000}, "id": 17, "weight": 1260}, 
            {"reward": {"assetId": "user:bean", "count": 50000}, "id": 18, "weight": 1}, 
            {"reward": {"assetId": "user:bean", "count": 20}, "id": 19, "weight": 22000}, 
            {"reward": {"assetId": "user:bean", "count": 100}, "id": 20, "weight": 5000}
          ]
        },
        {
          "gameId": 3,
          "name": "雪之城",
          "image": "/game/20201107/31e580dd9b4de5aa5c758ec49a8b1766.png",
          "energy": 300,
          "status": 1,
          "bgmap": "/bgmap.jpg",
          "cover": "/game/20201106/db4f79c58c58b28db520fce3e8bbcd28.png",
          "map": "/game/20201107/ff7b888923ec0db43b87b41f301a6c43.png",
          "covermap": "/game/20201107/6014d05290302139a5cbe7682fba0542.png",
          "diceReward":[
          {"reward": {"assetId": "user:bean", "count": 2000}, "id": 1, "weight": 7500}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 2, "weight": 20000}, 
          {"reward": {"assetId": "ore:fossil", "count": 1}, "id": 3, "weight": 200}, 
          {"reward": {"assetId": "user:bean", "count": 500}, "id": 4, "weight": 18000}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 5, "weight": 20000}, 
          {"reward": {"assetId": "user:bean", "count": 1000}, "id": 6, "weight": 16000}, 
          {"reward": {"assetId": "user:bean", "count": 20000}, "id": 7, "weight": 50}, 
          {"reward": {"assetId": "user:bean", "count": 500}, "id": 8, "weight": 18000}, 
          {"reward": {"assetId": "ore:gold", "count": 1}, "id": 9, "weight": 3000}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 10, "weight": 20000}, 
          {"reward": {"assetId": "user:bean", "count": 500}, "id": 11, "weight": 18000}, 
          {"reward": {"assetId": "user:bean", "count": 1000}, "id": 12, "weight": 14000}, 
          {"reward": {"assetId": "user:bean", "count": 5000}, "id": 13, "weight": 1500}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 14, "weight": 20000}, 
          {"reward": {"assetId": "user:bean", "count": 2000}, "id": 15, "weight": 7000}, 
          {"reward": {"assetId": "user:bean", "count": 500}, "id": 16, "weight": 15000}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 17, "weight": 18000}, 
          {"reward": {"assetId": "user:bean", "count": 100000}, "id": 18, "weight": 1}, 
          {"reward": {"assetId": "user:bean", "count": 1000}, "id": 19, "weight": 16000}, 
          {"reward": {"assetId": "user:bean", "count": 100}, "id": 20, "weight": 20000}
          ]
        }
      ],
      "energyInfo": {
        "rule": "http://newmtestapi.fqparty.com/rule.html",
        "commontoast": "赶快送出指定礼物获得体力吧",
        "lacktoast": "当前体力不足,赶快去送礼物获得体力吧"
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

    public function getTaoJibConf() {
        return json_decode(self::$CONFIG, true);
    }
}
