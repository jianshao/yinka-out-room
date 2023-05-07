<?php

namespace app\domain\user\model;

//用户信息详情的action行为模型
class MemberDetailAuditActionModel
{
    static public $avatar = "avatar";  //头像
    static public $nickname = "nickname";  //昵称
    static public $intro = "intro";//签名
    static public $wall = "wall";//照片墙
    static public $voice = "voice";//语音

    /**
     * @desc 用户用户信息需要展示审核的action
     * @return array
     */
    public static function getShowAuditActions()
    {
        return [
            self::$avatar,
            self::$nickname,
            self::$intro,
            self::$voice
        ];
    }


    /**
     * @param $type
     * @return string
     */
    public static function typeToMsg($type)
    {
        switch ($type) {
            case self::$avatar:
                $msg = '头像';
                break;

            case self::$nickname:
                $msg = '昵称';
                break;

            case self::$intro:
                $msg = '签名';
                break;

            case self::$wall:
                $msg = '照片墙';
                break;

            case self::$voice:
                $msg = '语音';
                break;
            default:
                $msg = "";
                break;
        }
        return $msg;
    }
}



