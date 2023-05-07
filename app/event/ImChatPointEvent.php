<?php


namespace app\event;


class ImChatPointEvent extends AppEvent
{
    public $userModel = null;
    public $code = 0;
    public function __construct($userModel, $code, $timestamp) {
        parent::__construct($timestamp);
        $this->userModel  = $userModel;
        $this->code = $code;
    }
}