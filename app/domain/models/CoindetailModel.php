<?php

namespace app\domain\models;

class CoindetailModel
{
    // sendgift 赠送礼物, cash 钻石提现, changes 钻石转M豆,guard 守护 vip购买vip ,BuyHarmmer买锤子，
    //BreakEgg消耗锤子，BreakEggGetGift砸蛋获得礼物，sendgiftFromBag消费砸蛋礼物,BuyHarmmer1金槌子，
    //FingerGameStart发起猜拳，FingerGameFight猜拳应战，FingerGameSettlement猜拳结算,sendgiftgame游戏送
    public $action = 0;
    // 房间id
    public $roomId = 0;
    // 用户id
    public $userId = 0;
    // 被赠送用户id
    public $toUserId = 0;
    // 礼物id
    public $giftId = 0;
    // 礼物数量
    public $giftCount = 0;
    // 明细说明
    public $content = '';
    //虚拟币
    public $coin = 0;
    // 送礼前，用户的虚拟币余额
    public $coinBefore= 0;
    // 送礼后，用户的虚拟币余额
    public $coinAfter= 0;
    // 消费时间
    public $addTime= '';
    // 对应表ss_chargedetail，用于记录充值时，用户获得的钱币
    public $chargeDetailId= 0;
    // 抢红包表red_packet_grab的id
    public $redPacketGrabId= 0;
    // 1语言 2一对一 3直播
    public $changeType= 0;
    // 1 虚拟币 2钻石 3vip购买
    public $status= 0;
    // 版本
    public $clientVersion= '';
    // 平台
    public $clientPlatform= '';
    // 礼物盒子礼物id
    public $randGift= 0;
}