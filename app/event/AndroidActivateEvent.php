<?php


namespace app\event;


class AndroidActivateEvent extends AppEvent
{
    public $clientInfo = null;

    public function __construct($timestamp, $clientInfo = [])
    {
        parent::__construct($timestamp);
        $this->clientInfo = $clientInfo;
    }
}