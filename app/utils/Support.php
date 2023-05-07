<?php

namespace app\utils;

use app\domain\exceptions\FQException;
use think\facade\Log;

class Support
{
    private $key;

    public function __construct($config)
    {
        $this->key = isset($config['key']) ? $config['key'] : "";
    }

    public function generateSign($data): string
    {
        $key = $this->key;
        if (is_null($key)) {
            throw new FQException('Missing Wechat Config -- [key]');
        }
        ksort($data);
        $string = md5($this->getSignContent($data) . '&key=' . $key);
        Log::debug(sprintf('Wechat Generate Sign Before UPPER data:%s,generateSign:%s', json_encode($data), $string));
        return strtoupper($string);
    }


    public static function getSignContent($data): string
    {
        $buff = '';
        foreach ($data as $k => $v) {
            $buff .= ($k != 'sign' && $v != '' && !is_array($v)) ? $k . '=' . $v . '&' : '';
        }
//        Log::debug('api Sign Content Before Trim', [$data, $buff]);
        return trim($buff, '&');
    }
}
