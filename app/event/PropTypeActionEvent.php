<?php


namespace app\event;


class PropTypeActionEvent extends AppEvent
{
    public $userId = 0;
    public $typeName = 0;
    public $action = '';
    public $params = null;

    public function __construct($userId, $typeName, $action, $params, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->typeName = $typeName;
        $this->action = $action;
        $this->params = $params;
    }
}