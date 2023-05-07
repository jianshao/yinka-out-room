<?php


namespace app\domain\room\model;


class RoomManagerModel
{
    /*
     * 前端显示类型
        NONE = 0, //普通
        MANAGER = 1, //管理员
        OWNER = 2, //房主
        GM= 3//官方
        SMANAGER = 4,//超级管理员
     * */
    public static $viewType = [
        0 => 1,
        1 => 2,
        3 => 3,
        2 => 4
    ];

    public $roomId = 0;
    public $userId = 0;
    public $createTime = 0;
    # 1房主，2超级管理， 0管理员
    public $type = 0;
}