<?php


namespace app\domain\redpacket;


class RedPacketModel
{
    public $id = 0;
    // 红包个数
    public $count = 0;
    // 红包总豆数
    public $totalBean = 0;
    // 发红包人
    public $sendUserId = 0;
    // 发红包时间
    public $sendTime = 0;
    // 红包倒计时（秒数）
    public $countdown = 0;
    // 哪个房间
    public $roomId = 0;
    // 红包状态0未生效1生效
    public $status = 0;
    // 创建时间
    public $createTime = 0;
    public $type = 0;
    // 支付订单号
    public $orderId = '';
    public $dealId = '';
}