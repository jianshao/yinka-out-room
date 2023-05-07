<?php


namespace app\event;


class UserRegisterEvent extends AppEvent
{
    public $userId = 0;
    public $clientInfo = null;

    public function __construct($userId, $timestamp, $clientInfo = [])
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->clientInfo = $clientInfo;
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