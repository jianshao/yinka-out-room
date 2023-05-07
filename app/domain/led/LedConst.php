<?php


namespace app\domain\led;

/**
 * led类型
 */

class LedConst
{
    #led位置
    //顶部
    public static $TOP = 'top';
    //中部
    public static $MIDDLE = 'middle';


    # led背景图配置的类型 如果新加类型 除了配置需要添加，这里也需要在此处添加
    //礼物顶部
    public static $GIFTTOP = 'giftTop';
    //礼物中部
    public static $GIFTMIDDLE = 'giftMiddle';
    //转盘顶部
    public static $TURNTABLETOP = 'turntableTop';
    //转盘中部
    public static $TURNTABLEMIDDLE = 'turntableMiddle';
    //宝箱顶部
    public static $BOXTOP = 'boxTop';
    //宝箱中部
    public static $BOXMIDDLE = 'boxMiddle';
    //爵位公爵
    public static $DUKE = 'duke';
    //爵位国王
    public static $DUKEKING = 'dukeKing';
    //svip
    public static $SVIP = 'svip';
    //淘金
    public static $TAOJING = 'taojing';
    //红包
    public static $HONGBAO = 'hongbao';
    //地鼠王
    public static $GOPHERKING = 'gopherKing';
    //KO地鼠王
    public static $KOGOPHERKING = 'koGopherKing';
    //挖宝
    public static $WABAO = 'wabao';
    //common
    public static $COMMON = 'common';

    # led背景图配置的总类型
    public static $ledTypeMap = ['giftTop', 'giftMiddle', 'turntableTop', 'turntableMiddle', 'boxTop', 'boxMiddle',
        'duke', 'dukeKing', 'svip', 'taojing', 'hongbao', 'gopherKing', 'koGopherKing', 'wabao', 'common'];
}