<?php

namespace app\query\user\cache;


//用户缓存 prefix
class CachePrefix
{

    //私有在线用户缓存桶  prefix+userId+sex
    public static $privateUserBucketPrefix = 'private_online_user_bucket_';
//    私有离线用户缓存桶 prefix+userId+sex
    public static $privateOfflineUserBucketPrefix = 'private_offline_user_bucket_';


//    共有在线用户性别缓存桶 prefix+sex;
    public static $publicBucketBoxSex = 'public_user_bucket_sex';


    public static $middMark = 999999;

    public static $expireTime = 86400;


    public static $nicknameCache = "nicknameLibraryCache";
    public static $lockCacheKey = "nicknameLibraryLock";

    public static $memberDetailAuditCache = "member_detail_audit_cache";

    public static $USER_INFO_CACHE= 'user:info:%s';
    public static $USER_ONLINE_SEX_CACHE ='user_online_%s_list';

    public static $search_elastic_user="search_for_elastic_user";
    public static $search_elastic_user_lock="search_for_elastic_user_lock";

    public static $search_elastic_user_empty_ttl = 30;
    public static $search_elastic_user_data_ttl = 120;
}


