<?php
namespace app\utils;

use app\facade\RequestAes as Request;

class HelperUnit {

    /**
     * @return array|mixed|string|null
     */
    public static function getToken()
    {
        $token=Request::header("token","");
        if (empty($token)) {//兼容老版
            $token=Request::param("token");
        }
        return $token;
    }


}