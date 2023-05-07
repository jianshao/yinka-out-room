<?php


namespace app\event;


class RoomInfoUpdateNoticeEvent extends AppEvent
{
    public $roomId = 0;

    public function __construct($roomId = 0, $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->roomId = $roomId;
    }


    public function jsonToModel($data)
    {
        $this->roomId = $data['room_id'] ?? 0;
        $this->timestamp = $data['timestamp'] ?? 0;
    }


    public function modelToJson()
    {
        return [
            'room_id' => $this->roomId,
            'timestamp' => $this->timestamp,
        ];
    }
}