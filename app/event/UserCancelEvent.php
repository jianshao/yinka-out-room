<?php


namespace app\event;


class UserCancelEvent extends AppEvent
{
    public $userId = 0;

    public function __construct($userId = 0, $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
    }


    public function jsonToModel($data)
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->timestamp = $data['timestamp'] ?? 0;
    }

    public function modelToJson()
    {
        return [
            'user_id' => $this->userId,
            'timestamp' => $this->timestamp,
        ];
    }
}