<?php


namespace app\domain\room\model;

class RoomBlackModel
{
    public $id = 0;
    public $roomId = 0;
    public $userId = 0;
    public $ctime = 0;
    public $longTime = 0;
    public $type = 0;

    public function diffForTime($unixTime)
    {
        return $this->ctime + $this->longTime - $unixTime;
    }
}