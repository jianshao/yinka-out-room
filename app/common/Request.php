<?php

namespace app\common;

use \app\facade\RawRequest;

//锁
class Request
{

    /**
     * @info request的middle获取驱动类型
     * @return bool
     */
    private static function isEncryptRequest()
    {
        $driver = config("config.EncryptDriver");
        if ($driver == 'encrypt') {
            return true;
        }
        return false;
    }

    /**
     * @param string $name
     * @param null $default
     * @param string $filter
     * @return array|mixed
     */
    public static function param($name = '', $default = null, $filter = '')
    {
        if (self::isEncryptRequest()) {
            return self::encriyptParam($name, $default, $filter);
        }
        return self::RawParam($name, $default, $filter);
    }

    /**
     * @param string $name
     * @param null $default
     * @param string $filter
     * @return array|mixed
     */
    private static function RawParam($name = '', $default = null, $filter = '')
    {
        return RawRequest::param($name, $default, $filter);
    }


    /**
     * @param string $name
     * @param null $default
     * @param string $filter
     * @return array|mixed
     */
    private static function encriyptParam($name = '', $default = null, $filter = '')
    {
        if (empty($name)) {
            return RawRequest::getMiddleware();
        }
        $data = RawRequest::middleware($name);
        if (empty($data)) {
            $data = [];
        }
        return RawRequest::input([$name => $data], $name, $default, $filter);
    }
}