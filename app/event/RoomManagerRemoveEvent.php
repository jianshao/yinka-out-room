<?php


namespace app\event;


class RoomManagerRemoveEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;
    public $opUserId = '';

    public function __construct($userId, $roomId, $opUserId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->opUserId = $opUserId;
    }
}