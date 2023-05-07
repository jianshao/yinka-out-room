<?php


namespace app\domain\user\model;


use app\utils\TimeUtil;

class UserRoomOnlineModel
{
    public $id = 0;
    // 用户ID
    public $userId = 0;
    // 房间id
    public $roomId = 0;
    // 今日日期年月日
    public $date = '';
    // 在线时间
    public $onlineSecond = 0;

    public function __construct($id, $userId = 0, $roomId = 0, $date = '', $onlineSecond = 0)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->date = $date;
        $this->onlineSecond = $onlineSecond;
    }
}