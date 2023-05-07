<?php


namespace app\event;


class RoomBlackUserEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;
    public $longTime = 0;
    public $opUserId = 0;
    public $roomOwnerUserId = 0;

    public function __construct($userId, $roomId, $longTime, $opUserId, $roomOwnerUserId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->longTime = $longTime;
        $this->opUserId = $opUserId;
        $this->roomOwnerUserId = $roomOwnerUserId;
    }
}