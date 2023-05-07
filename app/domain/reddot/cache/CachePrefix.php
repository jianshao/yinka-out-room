<?php

namespace app\domain\reddot\cache;


//红点缓存 prefix
class CachePrefix
{
    //  小红点for用户id key:prefix+userId type:hash
    public static $redHotForUserId = 'redhotForUserid:';

    public static $expireTime = 8640000;
}


