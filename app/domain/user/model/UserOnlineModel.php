<?php


namespace app\domain\user\model;


class UserOnlineModel
{
    // 用户ID
    public $userId = 0;
    // 今日日期年月日
    public $date = '';
    // 在线时间
    public $onlineSecond = 0;
    public $id = 0;

    public function __construct($userId=0, $date='', $onlineSecond=0, $id=0) {
        $this->userId = $userId;
        $this->date = $date;
        $this->onlineSecond = $onlineSecond;
        $this->id = $id;
    }
}