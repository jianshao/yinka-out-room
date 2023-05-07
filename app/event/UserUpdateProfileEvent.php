<?php


namespace app\event;


class UserUpdateProfileEvent extends AppEvent
{
    public $userId = 0;
    public $profile = null;

    public function __construct($userId=0, $profile=null, $timestamp=0)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->profile = $profile;
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