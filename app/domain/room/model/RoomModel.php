<?php

namespace app\domain\room\model;

class RoomModel
{
    public $roomId = 0;
    // 公会id
    public $guildId = 0;
    // 房间靓号
    public $prettyRoomId = 0;
    // 房主userId
    public $userId = 0;
    // 房间名称
    public $name = '';
    // 玩法介绍
    public $desc = '';
    // 房间头像
    public $image = '';
    // 欢迎语
    public $welcomes = '';
    // 房间分类
    public $roomType = 0;
    // 间标签
    public $tags = 0;
    // 1 九麦模式 2单麦
    public $mode = 1;
    // 房间密码
    public $password = '';
    // 锁定状态:0未锁定 1锁定
    public $lock = 0;
    // 创建时间
    public $createTime = 0;
    // 1自由麦 2非自由上麦
    public $isFreeMic = 1;
    // 1 默认开启 2心动值关闭
    public $isOpenHeartValue = 1;
    // 1 未通知 2粉丝通知
    public $fansNotices = 1;
    // 1 语言直播 2未语言直播
    public $liveType = 1;
    // 人气值
    public $visitorNumber = 0;
    // TODO 注释
    public $socitayId = 0;
    // 手动热度值
    public $visitorExternNumber = 0;
    // 环信房间ID
    public $hxRoom = 0;
    // 网房间号
    public $swRoom = 0;
    // 由用户数量产生的热度值
    public $visitorUsers = 0;
    // TODO 注释
    public $roomChannel = 0;
    // 房间背景图
    public $backgroundImage = '';
    // 是否推荐 0否 1是
    public $isHot = 0;
    // 状态 0未 1自动邀请新用户上麦
    public $isWheat = 0;
    // 标签图片地址
    public $tagImage = "";
    // 用户房间工会id
    public $guildIndexId = 0;
    // 房间营业时长
    public $type = '';
//    是否隐藏
    public $isHide = 0;
//    是否封禁
    public $isBlock = 0;
//    标签id
    public $tagId = 0;
}