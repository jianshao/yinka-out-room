<?php


namespace app\event;

//内部接口公会成员审核
class InnerAuditMemberEvent extends AppEvent
{
    public $userId = 0;
    public $profile = null;
    public $method = "";

    public function __construct($userId = 0, $profile = null, $method = "", $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->profile = $profile;
        $this->method = $method;
    }

    public function jsonToModel($data)
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->timestamp = $data['timestamp'] ?? 0;
        $this->method = $data['method'] ?? 0;
    }

    public function modelToJson()
    {
        return [
            'user_id' => $this->userId,
            'timestamp' => $this->timestamp,
            'method' => $this->method
        ];
    }
}