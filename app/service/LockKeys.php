<?php

namespace app\service;

class LockKeys
{
    public static function userKey($userId)
    {
        return 'glock_user:' . $userId;
    }

    public static function usernameKey($username)
    {
        return 'glock_username:' . $username;
    }

    public static function snsKey($snsType, $snsId)
    {
        return 'glock_sns:' . $snsType . ':' . $snsId;
    }

    public static function resetAttentionKey($userId)
    {
        return 'user_reset_attention:' . $userId;
    }
}