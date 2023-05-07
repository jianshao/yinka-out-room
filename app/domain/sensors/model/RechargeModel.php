<?php


namespace app\domain\sensors\model;


class RechargeModel
{

    // 订单Id
    public $orderId = '';

    // 场景
    public $scenario = '';

    // 对象ID
    public $targetId = '';

    // 房间ID
    public $roomId = '';

    // 音豆数量
    public $amount = 0;

    //余额
    public $balance = 0;

    //付款方式
    public $paymentMethod = '';

    // 付款途径
    public $paymentWay = '';

    // 是否成功
    public $isSuccess = false;

    // 失败原因
    public $failReason = '';
}