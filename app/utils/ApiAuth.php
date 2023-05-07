<?php

namespace app\utils;

use app\domain\exceptions\FQException;

class ApiAuth
{

    protected static $instance;

    private $diffSecond = 60;  //相差多少时间就废弃该请求，单位秒

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ApiAuth();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->key = config("config.apiSignSalt");
    }

    private function getplatForm($platForm)
    {
        $pos = strpos($platForm, "Android");
        if ($pos !== false) {
            return "Android";
        }
        $pos = strpos($platForm, "iOS");
        if ($pos !== false) {
            return "iOS";
        }
        return "";
    }

    /**
     * @param $paramArr array 请求的所有数据array
     * @param $platForm string 平台信息 example:[Android,iOS]
     * @param $source string 包源example:[fanqie yinlian,chacha]
     * @param $version string 版本号 example: 3.2.11
     * @return string
     */
    public function createSign($paramArr, $platForm, $source, $version)
    {
        $list = [];
        $list['token'] = $paramArr['token'] ?? "";
        $list['timestamp'] = $paramArr['timestamp'] ?? 0;
        $list['PLATFORM'] = $platForm;
        $list['SOURCE'] = $source;
        $list['VERSION'] = $version;
        ksort($list);
        $str = '';
        foreach ($list as $k => $v) {
            $str .= sprintf("%s%s%s%s", $k, '=', $v, '&');
        }
//        var_dump("计算api sign 之前的原始字符串:");
//        var_dump($str . 'key=' . $this->key);die;
        return strtoupper(md5($str . 'key=' . $this->key));
    }

    /**
     * @param $params
     * @return bool
     * @throws FQException
     */
    public function authSign($params, RequestHeaders $requestHeaders)
    {
        $enable = config("config.apiSignEnable");
        if ($enable !== "enable") {
            return true;
        }
        $originSign = $params['sign'] ?? "";
        if (empty($originSign)) {
            throw new FQException("未知错误500", 500);
        }
        unset($params['sign']);
        $platForm = $requestHeaders->getPlatFormOs();
        $source = $requestHeaders->source;
        $version = $requestHeaders->version;
        $sign = $this->createSign($params, $platForm, $source, $version);
        if ($originSign !== $sign) {
            throw new FQException("未知错误999", 500);
        }
        return true;
    }

    /**
     * @param $params
     * @return bool
     * @throws FQException
     */
    public function authTimestamp($params)
    {
        $enable = config("config.apiAuthTimestamp");
        if ($enable !== "enable") {
            return true;
        }
        $timestamp = isset($params['timestamp']) ? (int)$params['timestamp'] : 0;
        if (empty($timestamp)) {
            throw new FQException("未知错误1300", 400);
        }
        $unixTime = time();
        $diffTime = abs($unixTime - $timestamp);
        if ($diffTime > $this->diffSecond) {
            throw new FQException("未知错误1400", 400);
        }
        return true;
    }

}