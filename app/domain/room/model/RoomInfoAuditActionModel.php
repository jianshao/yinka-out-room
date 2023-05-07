<?php


namespace app\domain\room\model;

use app\utils\ArrayUtil;

class RoomInfoAuditActionModel
{
    public static $roomName = "roomName";  // 房间名称
    public static $roomWelcomes = "roomWelcomes";  // 房间欢迎语
    public static $roomDesc = "roomDesc"; // 房间公告

    public static function typeToMsg($type)
    {
        $Msg = [
            self::$roomName => "房间名称",
            self::$roomWelcomes => "房间欢迎语",
            self::$roomDesc => "房间公告",
        ];
        return ArrayUtil::safeGet($Msg, $type, '');
    }
}
