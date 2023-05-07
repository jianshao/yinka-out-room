<?php


namespace app\domain\room\model;


class RoomNameModel
{
    public $id = 0;
    // 荐房间名
    public $name = '';
    // 创建时间
    public $createTime = 0;
    // 状态：1展示 0不
    public $status = 0;
    // 1 交友 2游戏
    public $type = 1;
    // 房间类型ID
    public $roomTypeId = 0;
    // 房间类型父类id
    public $roomTypePid = 0;
}