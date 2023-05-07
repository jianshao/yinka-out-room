<?php


namespace constant;


class CommonConstant
{
    /*
     * redis
     */
    //登录用户的个人信息redis key 需要拼接用户id
    const ADMIN_USER_UID = 'admin_user_uid:';

    //登录用户的个人登录信息redis key 需要拼接用户id
    const ADMIN_USER_LOGIN_HISTORY = 'amin_user_login_history_uid:';

    //token key
    const TOKEN_KEY = 'HS256';

    /*
     * token不校验
     */
    const TOKEN_NO_CHECK_URI_MAP = [
        '/admin/signIn'
    ];
}