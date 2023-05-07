<?php

namespace app\service;

use AlibabaCloud\Client\AlibabaCloud;
use anerg\OAuth2\OAuth;
use app\common\AlibabaCloudCommon;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\SnsTypes;
use app\domain\user\dao\UserModelDao;
use AppleSignIn\ASDecoder;
use think\facade\Log;
use Exception;

class ThirdLoginService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ThirdLoginService();
        }
        return self::$instance;
    }

    public function appleLogin($appId, $appleUid, $appleToken)
    {
        try {
            $appleSignInPayload = ASDecoder::getAppleSignInPayload($appleToken);
            // $isValid = $appleSignInPayload->verifyUser($appleuid);
            $thirdId = $appleSignInPayload->getUser();
        } catch (Exception $e) {
            Log::error(sprintf("appleLogin getAppleSignInPayload error:%s errorstrace:%s", $e->getMessage(), $e->getTraceAsString()));
            // TODO log
            throw new FQException('账号错误请重试', 500);
        }

        if ($thirdId != $appleUid) {
            // TODO log
            throw new FQException('账号错误请重试', 500);
        }

        //暂存客户端三方token
        RedisCommon::getInstance()->getRedis()->setex('thirdIdTmp_' . md5($appleToken), 600, $thirdId);

        Log::info(sprintf('appleLogin %s %d %s', $appleToken, $appleUid, $thirdId));

        return [
            'snsId' => $thirdId
        ];
    }

    public function thirdLogin($thirdToken, $type, $config)
    {
        $conf = config("$config.THIRDLOGIN");
        $config = $conf[$type];
        $config['access_token'] = $thirdToken ?: '';
        $config['code'] = $thirdToken;
        if ($type == SnsTypes::$QOPENID) {
            try {
                $snsInfo = OAuth::qq($config)->userinfo();
                if (empty($snsInfo['openid']) || is_null($snsInfo['openid'])) {
                    throw new FQException('三方账号登录失败', 500);
                }
                $snsInfo['snsId'] = $snsInfo['openid'];
            } catch (\Exception $e) {
                Log::error(sprintf("thirdLogin qq error:%s", $e->getTraceAsString()));
                throw new FQException('三方账号登录失败', 500);
            }
        } elseif ($type == SnsTypes::$WXOPENID) {
            try {
                $snsInfo = OAuth::weixin($config)->userinfo();
                Log::INFO("thirdLogin:" . json_encode($snsInfo));
                $snsInfo['snsId'] = $snsInfo['unionid'];
            } catch (\Exception $e) {
                Log::error(sprintf("thirdLogin wechat error:%s", $e->getTraceAsString()));
                throw new FQException('三方账号登录失败', 500);
            }
        } else {
            throw new FQException('错误的登录类型', 500);
        }

        //暂存客户端三方token
        RedisCommon::getInstance()->getRedis()->setex('thirdIdTmp_' . md5($thirdToken), 600, $snsInfo['unionid']);

        Log::info(sprintf('thirdLogin %s %d %s', $thirdToken, $type, json_encode($snsInfo)));

        return $snsInfo;
    }

    public function getuiLogin($token, $gyUid, $config)
    {
        $timestamp = time();
        $getuiConf = config("$config.getui");
        $hashStr = $getuiConf['appkey'] . $timestamp . $getuiConf['mastersecret'];
        $getui_data = [
            'appId' => $getuiConf['appid'],
            'timestamp' => $timestamp,
            'sign' => hash256_encode($hashStr),
            'token' => $token,
            'gyuid' => $gyUid
        ];
        $getuiResStr = curlData($getuiConf['loginurl'], json_encode($getui_data), 'POST', 'json');
        $getuiRes = json_decode($getuiResStr, true);

        Log::info(sprintf('ThirdLoginService::getuiLogin gyUid=%s res=%s',
            $gyUid, $getuiResStr));
        if (!empty($getuiRes) && $getuiRes['errno'] == 0) {
            $uname = 0;
            if (array_key_exists('data', $getuiRes) && $getuiRes['data']['result'] == 20000) {
                $uname = @$getuiRes['data']['data']['pn'];
            }
            if ($uname == 0) {
                throw new FQException('登录读取失败请重试', 500);
            }
            return [
                'snsId' => $uname,
                'username' => $uname
            ];
        } else {
            throw new FQException('一键登录失败请重试', 500);
        }
    }


    public function aliLogin($accessToken)
    {
        try {
            $result = $this->aliGetMobile($accessToken);
//        $json_data='{"Message":"OK","RequestId":"9517AD93-5D58-4141-9DB1-1E9242C12C40","Code":"OK","GetMobileResultDTO":{"Mobile":"13811258123"}}';
//        $result=json_decode($json_data,true);
            if ($result['Code'] != "OK") {
                Log::error(sprintf('ali getMobile error:%s', json_encode($result)));
                throw new FQException("一键登录失败，请检查服务端", 500);
            }
            Log::info(sprintf('ali getMobile success:%s', json_encode($result)));

            $mobile = isset($result['GetMobileResultDTO']['Mobile']) ? $result['GetMobileResultDTO']['Mobile'] : "";
            if (empty($mobile)) {
                Log::error(sprintf('ali mobile error error:%s', json_encode($result)));
                throw new FQException("一键登录失败，请检查服务端", 500);
            }
            return [
                'snsId' => $mobile,
                'username' => $mobile
            ];
        } catch (Exception $e) {
            Log::info(sprintf('aliLogin exception error accesstoken=%s e=%s:strace=%s', $accessToken, $e->getMessage(), $e->getTraceAsString()));
            throw new FQException('一键登录失败请重试', 500);
        }

    }


    /**
     * @param $phoneId
     * @param $accessToken
     * @return array
     * @throws FQException
     */
    public function aliLoginVerify($phoneId, $accessToken)
    {
        try {
            $model = UserModelDao::getInstance()->loadUserModel($phoneId);
            if (empty($model)) {
                throw new FQException("用户不存在", 500);
            }
            $phone = $model->mobile;
            $result = AlibabaCloudCommon::VerifyMobile($phone, $accessToken);

            if (!isset($result['body']['GateVerifyResultDTO']['VerifyResult']) || $result['body']['GateVerifyResultDTO']['VerifyResult'] != "PASS") {
                Log::error(sprintf('ali getMobile error:%s', json_encode($result)));
                throw new FQException("verify失败，请检查服务端", 500);
            }
            Log::info(sprintf('ali LoginVerify success:%s', json_encode($result)));
            return [
                'snsId' => $phone,
                'username' => $phone
            ];
        } catch (Exception $e) {
            Log::info(sprintf('aliLogin exception error accesstoken=%s e=%s:strace=%s', $accessToken, $e->getMessage(), $e->getTraceAsString()));
            throw new FQException('一键登录失败请重试', 500);
        }
    }


    /**
     * @param $accessToken
     * @response {"Message":"OK","RequestId":"9517AD93-5D58-4141-9DB1-1E9242C12C40","Code":"OK","GetMobileResultDTO":{"Mobile":"13811258123"}}
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     */
    private function aliGetMobile($accessToken)
    {
        AlibabaCloud::accessKeyClient(config('config.OSS.ACCESS_KEY_ID'), config('config.OSS.ACCESS_KEY_SECRET'))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dypnsapi')
                ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('GetMobile')
                ->method('POST')
                ->host('dypnsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'AccessToken' => $accessToken,
                    ],
                ])
                ->request();
            $data = $result->toArray();
            Log::info(sprintf('aliGetMobile %s', json_encode($data)));
            return $data;
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }
    }
}