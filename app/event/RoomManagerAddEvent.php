<?php


namespace app\event;


class RoomManagerAddEvent extends AppEvent
{
    public $userId = 0;
    public $roomId = 0;
    public $opUserId = '';
    public $userIdentity=0;

    public function __construct($userId, $roomId, $opUserId, $managerType, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->opUserId = $opUserId;
        $this->userIdentity = $managerType;
    }
}