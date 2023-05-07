<?php


namespace app\domain\thirdpay\service;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * @desc 微信相关
 * Class WeChatService
 * @package app\domain\thirdpay\chinaums
 */
class WeChatService
{
    private $appid = '';
    private $appSecret = '';
    private $wxCode2SessionUrl = 'https://api.weixin.qq.com/sns/jscode2session';
    private $wxGetTokenUrl = 'https://api.weixin.qq.com/cgi-bin/token';
    private $wxGetLinkUrl = 'https://api.weixin.qq.com/wxa/generate_urllink';

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->appid = config("config.chinaaums.appid");
        $this->appSecret = config("config.chinaaums.appSecret");
    }

    /**
     * @desc 获取微信openid
     * @param string $code
     * @return array
     *  array:2 [
     *      "session_key" => "vfYiqeFW1o+uR65eRc1tUQ=="
     *      "openid" => "oy5-E5btOxiWK_kaURjPSMF9GBYk"
     * ]
     */
    public function getWxOpenid(string $code = ''): array
    {
        if (!$code) {
            return [];
        }
        $requestData = [];
        $requestData["appid"] = $this->appid;
        $requestData["secret"] = $this->appSecret;
        $requestData["js_code"] = $code;
        $requestData["grant_type"] = "authorization_code";
        $response = curlData($this->wxCode2SessionUrl, $requestData);

        Log::info(sprintf('WeChatService::getWxOpenid $code=%d response=%s', $code, $response));
        return json_decode($response, true);
    }

    /**
     * @desc 获取微信AccessToken
     * @return array
     *  array:2 [
     *      "access_token" => "56_Cf-95uXiDUnGLR7JjMhMaQlCCcq7aK6yA_V2JZKS4uZeNtQJof_K5CA3qowJvoh4pNRALzJov0NW86HeK_z17PZoJpkhYigGjAK8nRfqHrC8dGPvgJy4fu0FIF97GhSNDfxTu5JoNjSpJFB4YXZcACAONP"
     *      "expires_in" => 7200
     * ]
     */
    public function getAccessToken($isForce = false)
    {
        // 先从缓存获取
        $redis = RedisCommon::getInstance()->getRedis();
        $redisKey = 'chinaums:wxApplet:access_token';

        // $isForce  true从微信获取AccessToken   false先查询缓存
        if (!$isForce){
            $cacheAccessToken = $redis->get($redisKey);
            if ($cacheAccessToken) {
                return $cacheAccessToken;
            }
        }

        $requestData = [];
        $requestData["appid"] = $this->appid;
        $requestData["secret"] = $this->appSecret;
        $requestData["grant_type"] = "client_credential";
        $response = curlData($this->wxGetTokenUrl, $requestData);
        Log::channel(['pay', 'file'])->info(sprintf('WeChatService::getAccessToken response=%s', $response));

        $response = json_decode($response, true);
        $accessToken = ArrayUtil::safeGet($response, 'access_token');
        if ($accessToken) {
            $redis->set($redisKey, $accessToken, 7100);
        }

        return $accessToken;
    }

    /**
     * @desc 微信url_link返回值
     *     $isForce  true从微信获取AccessToken   false先查询缓存
     * @param $linkParams
     */
    private function getUrlLinkResponse($linkParams, $isForce = false)
    {
        // 获取链接前先回去 access_token
        $accessToken = $this->getAccessToken($isForce);
        $url = $this->wxGetLinkUrl . '?access_token=' . $accessToken;

        $linkParams['expire_type'] = 1;  // 小程序 URL Link 失效类型，失效时间：0，失效间隔天数：1
        $linkParams['expire_interval'] = 20; // 到期失效的URL Link的失效间隔天数。生成的到期失效URL Link在该间隔时间到达前有效。最长间隔天数为30天。expire_type 为 1 必填

        $response = curlData($url, json_encode($linkParams), 'POST');
        Log::channel(['pay', 'file'])->info(sprintf('WeChatService::getUrlLink response=%s', $response));

        return json_decode($response, true);
    }

    /**
     * @desc 获取微信url_link
     * @param $linkParams
     * @return string
     * @throws FQException
     */
    public function getUrlLink($linkParams): string
    {
        $response = $this->getUrlLinkResponse($linkParams);

        $errCode = ArrayUtil::safeGet($response, 'errcode');
        if ($errCode !== 0) {
            Log::channel(['pay', 'file'])->error(sprintf('WeChatService::getUrlLink error response=%s', json_encode($response)));
            $response = $this->getUrlLinkResponse($linkParams, true);
            $errCode = ArrayUtil::safeGet($response, 'errcode');
            if ($errCode !== 0) {
                throw new FQException('获取url_link失败', 500);
            }
        }
        return ArrayUtil::safeGet($response, 'url_link');
    }
}