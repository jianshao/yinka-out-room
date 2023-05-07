<?php


namespace app\event;


class RedPacketGrabEvent extends AppEvent
{
    public $userId = 0;
    public $redPacketId = 0;
    public $detailId = '';

    public function __construct($userId, $redPacketId, $detailId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->redPacketId = $redPacketId;
        $this->detailId = $detailId;
    }
}