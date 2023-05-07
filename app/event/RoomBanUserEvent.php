<?php


namespace app\event;


class RoomBanUserEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;
    public $longTime = 0;
    public $ban = false;
    public $opUserId = 0;
    public $roomOwnerUserId = 0;

    public function __construct($userId, $roomId, $longTime, $ban, $opUserId, $roomOwnerUserId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->longTime = $longTime;
        $this->ban = $ban;
        $this->opUserId = $opUserId;
        $this->roomOwnerUserId = $roomOwnerUserId;
    }
}