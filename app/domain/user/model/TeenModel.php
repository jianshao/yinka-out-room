<?php

namespace app\domain\user\model;


/**
 * @Info 青少年
 * Class TeenModel
 * @package app\domain\user\model
 */
class TeenModel
{
    // 是否开启了青少年模式 （1开启 2 关闭）
    public $Status = 0;
    // 青少年倒计时的结束时间戳 如果已解锁了为0
    public $Endtime = 0;
    // 宵禁的开始时间戳 今天的22点
    public $BlockStartTime = 0;
    // 宵禁的结束时间戳 第二天的6点 //如果凌晨0点到凌晨6点 请求为上一次的开始结束时间节点， 6点后更新第二天的
    public $BlockEndTime = 0;
}


