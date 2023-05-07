<?php

namespace app\common;

use app\facade\RawRequest;

//锁
class RequestAes
{
    /**
     * @param string $name
     * @param null $default
     * @param string $filter
     * @return array|mixed
     */
    public function param($name = '', $default = null, $filter = '')
    {
        if (empty($name)) {
            return RawRequest::getMiddleware();
        }
        $data = RawRequest::middleware($name);
        if ($data===null) {
            $data = $default;
        }
        return RawRequest::input([$name => $data], $name, $default, $filter);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public function middleware($name, $default = null)
    {
        return RawRequest::middleware($name, $default);
    }


    /**
     * @param string $name
     * @param string|null $default
     * @return array|string|null
     */
    public function header(string $name = '', string $default = null)
    {
        return RawRequest::header($name, $default);
    }

    /**
     * @return string
     */
    public function ip()
    {
        return RawRequest::ip();
    }

    /**
     * @param $name
     * @return array|\think\file\UploadedFile|null
     */
    public function file($name)
    {
        return RawRequest::file($name);
    }
}