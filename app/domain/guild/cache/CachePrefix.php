<?php

namespace app\domain\guild\cache;


//公会缓存 prefix
class CachePrefix
{

//    公会房间热度key
    public static $guildRoomHot = 'guild_room_hot';   //公会room单元的热度key  type:hash
//    工会房间人气值
    public static $popularRoom = 'popular_value_room';   //公会room的人气值  type:hash
    //首页热门房间人气值
    public static $HomeRoomHot = 'home_room_hot';   //首页热门房间人气值 type:hash

    public static $RecreationRoomHot = 'recreation_room_hot';   //娱乐页房间人气值 type:hash

    public static $roomLock = 'room_lock';   //锁房的房间key  type:set

    public static $guildHotRoomSortBucket = 'guild_hot_room_sort_bucket';     //公会热度房间排序bucket type:zset

    public static $homeHotRoomSortBucket = 'home_hot_room_sort_bucket';     //首页推荐房间排序bucket type:zset

    public static $recreationHotRoomSortBucket = 'recreation_hot_room_sort_bucket';     //娱乐页人气房间排序bucket type:zset

    public static $muaHotRoomSortBucket = 'mua_hot_sort_bucket';     //mua热度房间排序bucket type:zset

    public static $muaKingKongSortBucket = 'mua_king_kong_sort_bucket';     //mua金刚位房间排序bucket type:zset

    public static $RoomUserList = 'go_room_';

    public static $bucketExpireTime = 300;      //zset过期时间

    public static $expireTime = 86400;        //item过期时间
}


