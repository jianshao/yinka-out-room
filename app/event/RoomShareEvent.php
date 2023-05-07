<?php


namespace app\event;

//分享房间
class RoomShareEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;

    public function __construct($userId, $roomId, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
    }
}