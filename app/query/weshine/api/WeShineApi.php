<?php

namespace app\query\weshine\api;


use app\domain\exceptions\FQException;
use think\facade\Log;

class WeShineApi
{
    protected static $instance;
    protected $secret = 'bfdc1d6e403ac3db9cf0f1d4e165ed76';
    protected $openId = '1640939269';
    protected $url = [
        'shineSearch' => "http://api.open.weshineapp.com/1.0/search",
        'shineHotLook' => "http://api.open.weshineapp.com/1.0/hot"
    ];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new WeShineApi();
        }
        return self::$instance;
    }

    public function getSign($timeStamp)
    {
        return strtoupper(md5(sprintf('%s#%s#%s', $this->openId, $this->secret, $timeStamp)));
    }

    public function buildParams(&$data)
    {
        $timestamp = msectime();
        $data['openid'] = $this->openId;
        $data['timestamp'] = $timestamp;
        $data['sign'] = $this->getSign($timestamp);
        return http_build_query($data);
    }

    public function getResponse($name, $data)
    {
        $paramsString = $this->buildParams($data);
        $url = $this->url[$name] . '?' . $paramsString;
        Log::info(sprintf('WeShineApi:request name:%s url:%s', $name, $url));
        try {
            $response = curlData($url, []);
            Log::info(sprintf('WeShineApi:request name:%s url:%s response:%s', $name, $url, $response));
            $result = json_decode($response, true);
            if (isset($result['meta']['status']) && $result['meta']['status'] == 200) {
                if (isset($result['data']) && isset($result['pagination'])) {
                    $res['list'] = $result['data'];
                    $res['pageInfo'] = [
                        'totalCount' => $result['pagination']['totalCount'] ?? 0,
                        'totalPage' => $result['pagination']['totalPage'] ?? 0,
                        'count' => $result['pagination']['count'] ?? 0,
                        'offset' => $result['pagination']['offset'] ?? 0,
                    ];
                } else {
                    $res = [];
                }
                return $res;
            } else {
                throw new FQException('未知错误，请重试', 500);
            }
        } catch (\Exception $e) {
            Log::error(sprintf('WeShineApi:response:url:%s error:%s,strace:%s', $url, $e->getMessage(), $e->getTraceAsString()));
        }
    }

}