<?php


namespace app\event;


class RoomAttentionEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;

    public function __construct($userId, $roomId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
    }
}