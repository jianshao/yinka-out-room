<?php


namespace app\event;


class VisitUserInfoEvent extends AppEvent
{
    public $userId = 0;
    public $visitUserId = 0;
    public $isVisit = 0;
    public function __construct($userId, $visitUserId, $isVisit, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->visitUserId = $visitUserId;
        $this->isVisit = $isVisit;
    }
}