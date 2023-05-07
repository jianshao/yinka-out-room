<?php

namespace app\query\room\cache;


//房间缓存 prefix
class CachePrefix
{

    //私有在线用户缓存桶  prefix+userId+sex
    public static $privateUserBucketPrefix = 'private_online_user_bucket_';
//    私有离线用户缓存桶 prefix+userId+sex
    public static $privateOfflineUserBucketPrefix = 'private_offline_user_bucket_';

    //roomtype模型缓存key prefix+id
    public static $roomTypeModelPrefix = 'roomtype_model_cache_';

//    RoomModelCache prefix+id
    public static $roomModelCache = 'room_model_cache_';

    public static $GuildQueryRoomModelCache = 'guild_query_room_model_cache';
    public static $PersonQueryRoomModelCache = 'person_query_room_model_cache';

    public static $onlinePersonRooms = 'online_person_rooms';

    public static $onlineGuildRooms = 'online_guild_rooms';

//    mua金刚位房间cachekey
    public static $muaRoomKingKong = 'mua_room_king_kong';

    //    mua金刚位房间cachekey
    public static $muaRecommend = 'mua_new_room_recommend';

//    共有在线用户性别缓存桶 prefix+sex;
    public static $publicBucketBoxSex = 'public_user_bucket_sex';

    public static $expireTime = 3600;

//    首页模拟恋爱set key
    public static $randRoomKey = 'index_rand_room_set';

//    男神房间类型
    public static $roomTypeMan = 23;
//    女神房间类型
    public static $roomTypeWoman = 26;

//    首页热门房间
    public static $indexHotRoomKey = 'index_hot_room_set';
//    派对页推荐房间
    public static $partRecommendRoomKey = 'part_recommend_room_set';
//    正在pk的房间集合
    public static $guild_pk_rooms='guild_pk_rooms';

    public static $search_elastic_room="search_for_elastic_room";
    public static $search_elastic_room_lock="search_for_elastic_room_lock";

    public static $search_elastic_room_empty_ttl = 30;
    public static $search_elastic_room_data_ttl = 120;

}


