<?php


namespace app\event;

//房间停留时间
class RoomStaySecondEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;
    public $second = 0; //总秒数

    public function __construct($userId, $roomId, $second, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->second = $second;
    }
}