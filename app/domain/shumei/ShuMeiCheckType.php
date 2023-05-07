<?php


namespace app\domain\shumei;


class ShuMeiCheckType
{


    public static $AUDIO_STREAM_CHECK_SWITCH = 0; //音频流检测开关


    //文本
    public static $TEXT_FORUM_EVENT = 'dynamic';  //帖子文本
    public static $TEXT_NICKNAME_EVENT = 'nickname'; //昵称
    public static $TEXT_COMMENT_EVENT = 'comment'; //评论
    public static $TEXT_INTRO_EVENT = 'profile'; //个人简介
    public static $TEXT_MESSAGE_EVENT = 'message'; //私聊
    public static $TEXT_ROOM_NAME_EVENT = 'room_name'; //房间名称
    public static $TEXT_ROOM_DESC_EVENT = 'room_desc'; //房间公告
    public static $TEXT_ROOM_WELCOMES_EVENT = 'room_welcomes'; //房间欢迎语

    //图片
    public static $IMAGE_HEAD_EVENT = 'headImage'; //头像
    public static $IMAGE_ALBUM_EVENT = 'album'; //相册
    public static $IMAGE_FORUM_EVENT = 'dynamic'; //帖子
    public static $IMAGE_MESSAGE_EVENT = 'message'; //私聊


    //检测的风险类型
    public static $TEXT_TYPE = 'ALL'; //文本
    public static $IMAGE_TYPE = 'POLITICS_PORN_AD_BAN'; //图片
    public static $AUDIO_STREAM_STREAM_TYPE = 'POLITICAL_PORN_MOAN_AD'; //音频流



}