<?php

namespace app\domain\version\cache;


class VersionCheckCache
{

    const prefix = 'version:';

    //所有用户 redis set 类型 存储用户uid
    public static $userListKey = self::prefix.'user_list';

    //所有房间 redis set 类型 存储房间id
    public static $roomListKey = self::prefix.'room_list';

    //动态列表 redis zSet 类型 存储动态id
    public static $forumListKey = self::prefix.'forum_list';

    //话题动态 redis zSet 类型 存储动态id %d为话题id
    public static $topicForumListKey = self::prefix.'forum_topic:tid:%d';

    //在线交友 redis zSet 类型 存储用户id
    public static $onlineUserListKey = self::prefix.'online_user_list';

    //热门房间 redis set 类型 存储房间id
    public static $hotRoomListKey = self::prefix.'hot_room_list';

    //分类下房间列表 redis zSet 类型 存储房间id
    public static $roomTypeListKey = self::prefix.'room_type_list:%d'; //8889 热门 8888 女神 8887 男神 9999 推荐 9998 交友 9997 闲聊

    //人气推荐房间 redis set 类型 存储房间id
    public static $partRecommendRoomKey = self::prefix.'part_recommend_room_list';

    //首页榜单 redis zSet 类型 存储用户id
    // 财富值 version:Rich_Day_0_0 日 version:Rich_Week_0_0 周 version:version:Rich_Month_0_0 月
    // 魅力值 version:Like_Day_0_0 日 version:Like_Week_0_0 周 version:version:Like_Month_0_0 月
    public static $rankListKey = self::prefix.'%s_%s_%s_%s';

}


