<?php

namespace app\domain\recall\model;

// 用户召回活动push配置
class PushRecallType
{
    public static $GETUIPUSH = 'getuipush';   //个推push
    public static $CHUANGLANSMS = 'chuanglansms';   //创蓝短信
    public static $RTDSMS = 'rtdsms';       //蓉通达短信
    public static $ALIVOICE = 'alivoice';       //阿里语音
}


