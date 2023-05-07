<?php


namespace app\event;


class SendRedPacketEvent
{
    public $userId = 0;
    public $roomId = 0;
    public $redPacketId = 0;
    public $totalBean = 0;
    public $count = 0;
    public $orderId = 0;

    public function __construct($userId, $roomId, $redPacketId, $totalBean, $count ,$orderId) {
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->redPacketId = $redPacketId;
        $this->totalBean = $totalBean;
        $this->count = $count;
        $this->orderId = $orderId;
    }
}