<?php


namespace app\domain\activity\gameVote;


class Config
{
    public static $CONF = '
        {
            "startTime":"2022-02-19 00:00:00",
            "stopTime":"2022-02-21 23:59:59",
            "senderAssets": [{
                "assetId": "prop:260",
                "count": 7 ,
                "name": "童年回忆头像框*7天",
                "img": "Public/Uploads/image/logo.png"
            }],
            "list":[
                {
                    "id":1,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1644996243.151385.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/saolei.mp4",
                    "name":"扫雷",
                    "poster":"https://image2.fqparty.com/20220216/saolei_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/saolei.png"
                },
                {
                    "id":2,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1644996324.531959.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/UMO.mp4",
                    "name":"UMO",
                    "poster":"https://image2.fqparty.com/20220216/umo_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/UMO.png"                
                },
                {
                    "id":3,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1644996403.157763.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/pengpengwozuiqiang.mp4",
                    "name":"碰碰我最强",
                    "poster":"https://image2.fqparty.com/20220216/pengpengwozuiqiang_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/pengpengwozuiqiang.png"                
                },
                {
                    "id":4,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1644996512.301592.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/feibiaodaren.mp4",
                    "name":"飞镖达人",
                    "poster":"https://image2.fqparty.com/20220216/feibiaodaren_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/feibiaodaren.png"                
                },
                {
                    "id":5,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1644996820.027963.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/wuziqi.mp4",
                    "name":"五子棋",
                    "poster":"https://image2.fqparty.com/20220216/wuziqi_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/wuziqi.png"                
                },
                {
                    "id":6,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1644996883.125316.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/heibaiqi.mp4",
                    "name":"黑白棋",
                    "poster":"https://image2.fqparty.com/20220216/heibaiqi_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/heibaoqi.png"               
                },
                {
                    "id":7,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1644996967.098433.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/shuzizhadan.mp4",
                    "name":"数字炸弹",
                    "poster":"https://image2.fqparty.com/20220216/shuzizhadan_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/shuzizhadan.png"                
                },
                {
                    "id":8,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1645001316.281532.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/zhuoqiu.mp4",
                    "name":"桌球",
                    "poster":"https://image2.fqparty.com/20220216/zhuoqiu_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/zhuoqiu.png"                
                },
                {
                    "id":9,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1645001501.520100.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/dafuweng.mp4",
                    "name":"大富翁",
                    "poster":"https://image2.fqparty.com/20220216/dafuweng_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/dafuweng.png"                
                },
                {
                    "id":10,
                    "url":"https://image2.fqparty.com/20220216/FinalVideo_1645002061.664161.MOV",
                    "mp4_url":"https://image2.fqparty.com/20220216/feixingqi.mp4",
                    "name":"飞行棋",
                    "poster":"https://image2.fqparty.com/20220216/feixingqi_poster.png",
                    "imgUrl":"https://image2.fqparty.com/20220216/feixingqi.png"                
                }
            ]
        }
    ';


    public static function loadConf()
    {
        return json_decode(self::$CONF, true);
    }
}