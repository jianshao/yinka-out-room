<?php


namespace app\event;


class UserLoginEvent extends AppEvent
{
    public $userId = 0;
    public $lastLoginTime = 0;
    public $clientInfo=null;
    public $isRegister=false;

    public function __construct($userId = 0, $lastLoginTime = 0, $timestamp = 0, $clientInfo = [], $isRegister = false)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->lastLoginTime = $lastLoginTime;
        $this->clientInfo = $clientInfo;
        $this->isRegister = $isRegister;
    }

    public function jsonToModel($data)
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->lastLoginTime = $data['last_login_time'] ?? 0;
        $this->timestamp = $data['timestamp'] ?? 0;
    }

    public function modelToJson()
    {
        return [
            'user_id' => $this->userId,
            'last_login_time' => $this->lastLoginTime,
            'timestamp' => $this->timestamp,
        ];
    }
}