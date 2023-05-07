<?php


namespace app\domain\activity\yearTicket;


use app\domain\exceptions\FQException;

class Config
{
//    prizeGifts 中奖的礼物ids
//    ticketGifts 年票礼物ids
//    ticketRatio 年票的年度积分比率
//    levelUpgrade 晋级配置
//目前定年度活动时间定：
//1月11日23：59：59选出前十公会
//
//1月13日23：59：59选出前七公会
//
//1月15日23：59：59选出前五公会
//
//1月16日23：59：59前三公会
//
//1月17日23：59：59第一公会

//测试
//"prizeGifts":[250,251,254,294,387],
//"ticketGifts":[543],

// 线上
//"prizeGifts":[538,236,384],
//"ticketGifts":[543],
    public static $CONF = '
        {
            "startTime":"2022-01-13 00:00:00",
            "stopTime":"2022-01-19 23:59:59",
            "prizeGifts":[538,236,545],
            "ticketGifts":[543],
            "ticketRatio":10000,
            "levelUpgrade":[{
                "id":1,
                "startTime":"2022-01-13 00:00:00",
                "endTime":"2022-01-15 23:59:59",
                "displayName":"预选赛",
                "rankNumber":10
            },{
                "id":2,
                "startTime":"2022-01-16 00:00:00",
                "endTime":"2022-01-17 23:59:59",
                "displayName":"初赛",
                "rankNumber":5
            },{
                "id":3,
                "startTime":"2022-01-18 00:00:00",
                "endTime":"2022-01-18 23:59:59",
                "displayName":"晋级赛",
                "rankNumber":3
            },{
                "id":4,
                "startTime":"2022-01-19 00:00:00",
                "endTime":"2022-01-19 23:59:59",
                "displayName":"决赛",
                "rankNumber":1
            }],
            "senderAssets": [{
                "assetId": "gift:543",
                "count": 1 ,
                "name": "年票",
                "img": "Public/Uploads/image/logo.png"
            }]
        }';

    public static function loadConf()
    {
        $data=json_decode(self::$CONF, true);
        if (empty($data)){
            throw new FQException("yearticket error",500);
        }
        return $data;
    }
}