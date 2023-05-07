<?php


namespace app\event;


class RoomLockEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;
    public $password = '';

    public function __construct($userId = 0, $roomId = 0, $password = "", $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->password = $password;
    }


    public function jsonToModel($data)
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->roomId = $data['room_id'] ?? 0;
        $this->timestamp = $data['timestamp'] ?? 0;
    }

    public function modelToJson()
    {
        return [
            'user_id' => $this->userId,
            'room_id' => $this->roomId,
            'timestamp' => $this->timestamp,
        ];
    }
}