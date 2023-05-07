<?php


namespace app\domain\sensors\model;


class ConsumeBeanModel
{
    // 对象id
    public $targetId = '';

    // 房间id
    public $roomId = '';

    //场景
    public $scenario = '';

    //礼物类型
    public $giftType = '';

    //改变数量
    public $amount = 0;

    //余额
    public $balance = 0;
}