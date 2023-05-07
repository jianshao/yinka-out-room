<?php


namespace app\domain\bi;


class BIConfig
{
    // 资产类型
    public static $PROP_TYPE = 1;
    public static $BANK_TYPE = 2;
    public static $GIFT_TYPE = 3;
    public static $BEAN_TYPE = 4;
    public static $DIAMOND_TYPE = 5;
    public static $COIN_TYPE = 6;
    public static $ENERGY_TYPE = 7;
    public static $ORE_TYPE = 8;

    //eventId
    public static $CHARGE_EVENTID = 10001;     //充值
    public static $SEND_GIFT_EVENTID = 10002;   //送礼
    public static $RECEIVE_GIFT_EVENTID = 10003;    //收礼
    public static $DIAMOND_EXCHANGE_EVENTID = 10004;        //钻石兑换
    public static $BUY_EVENTID = 10005;     //商城购买
    public static $TASK_EVENTID = 10006;    //任务奖励
    public static $DUKE_EVENTID = 10007;    //爵位变化
    public static $VIP_EVENTID = 10008;     //vip变化
    public static $ACTIVITY_EVENTID = 10009;    //活动消耗
    public static $REDPACKETS_EVENTID = 10010;      //红包
    public static $PRIVILEGE_REWARD_EVENTID = 10011;        //等级特权
    public static $REDPACKETS_GRAB_EVENTID = 10012;         //抢购包
    public static $REDPACKETS_RETURN_EVENTID = 10013;       //返还红包
    public static $REPLACE_CHARGE_EVENTID = 10014;          //工会代充
    public static $WITHDRAW_PRETAKEOFF_EVENTID = 10015; // 提现预扣除
    public static $WITHDRAW_SUCCESS_EVENTID = 10016;    // 提现成功
    public static $WITHDRAW_REFUSE_EVENTID = 10017;     // 提现拒绝
    public static $GM_ADJUST = 10020; // 运营调整
    public static $OPEN_GIFT = 10021; // 打开背包礼物 如：福袋
    public static $ACTIVITY_EXPIRED_EVENTID = 10022; //淘金活动过期
    public static $PROP_ACTION_EVENTID = 10023; //道具action
    public static $MALL_SEND_EVENTID = 10024;     //商城赠送
    public static $MALL_RECEIVE_EVENTID = 10025;  //商城接收
    public static $GIFT_ACTION_EVENTID = 10026; //礼物action
    public static $COIN_EXCHANGE_EVENTID = 10027;        //音豆兑换金币
    public static $SUPER_REWARD_EVENTID = 10028;        //音豆兑换金币
    public static $SYSTEM_SEND_GIFT_EVENTID = 10029;   //系统补给送礼
}