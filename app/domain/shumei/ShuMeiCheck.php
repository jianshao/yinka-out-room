<?php
//数美 图文音视 检测
namespace app\domain\shumei;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\common\RedisCommon;
use app\query\weshine\service\WeShineService;
use app\utils\CommonUtil;
use think\facade\Log;


class ShuMeiCheck
{

    private $AccessKey;
    private $AppId;


    protected static $instance;

    //单例
    public static function getInstance(): ShuMeiCheck
    {
        if (!isset(self::$instance)) {
            self::$instance = new ShuMeiCheck();
        }
        return self::$instance;
    }

    /**
     * 参数初始化
     */
    public function __construct()
    {
        $source = app('request')->header('source', '');
        $conf = config($source . "config");
        if (isset($conf['shumei'])) {
            $this->AccessKey = config($source . 'config.shumei.AccessKey');
            $this->AppId = config($source . 'config.shumei.AppId');
        } else {
            $this->AccessKey = config('config.shumei.AccessKey');
            $this->AppId = config('config.shumei.AppId');
        }
    }

    /**
     * @info 文本检测
     * @param $text string  文本
     * @param $eventId string 事件标识
     * @param $userId int 用户uid
     * @return bool
     */
    public function textCheck($text, $eventId, $userId)
    {
        return true;
        try {
            $requestHost = 'http://api-text-bj.fengkongcloud.com/text/v4';
            $payLoad = [
                'accessKey' => $this->AccessKey,
                'appId' => $this->AppId,
                'eventId' => $eventId,
                'type' => ShuMeiCheckType::$TEXT_TYPE,
                'data' => [
                    'text' => $text,
                    'tokenId' => (string)$userId,
                ]
            ];
            $jsonLoad = json_encode($payLoad);
            $res = curlData($requestHost, $jsonLoad, 'POST', 'json');
            Log::info(sprintf("ShuMeiCheck textCheck request:%s", $jsonLoad));
            Log::info(sprintf("ShuMeiCheck textCheck response:%s", $res));
            $data = json_decode($res, true);
            if ($data && $data['code'] == 1100 && $data['riskLevel'] == 'PASS') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::error(sprintf("ShuMeiCheck textCheck error errCode:%s errEx:%s", $e->getCode(), $e->getMessage()));
            return $text;
        }


    }

    /**
     * @info 文本检测 将敏感词过滤成*
     * @param $text string  文本
     * @param $eventId string 事件标识
     * @param $userId int 用户uid
     * @return bool
     */
    public function filterText($text, $eventId, $userId)
    {
        return $text;
        try {
            $requestHost = 'http://api-text-bj.fengkongcloud.com/text/v4';
            $payLoad = [
                'accessKey' => $this->AccessKey,
                'appId' => $this->AppId,
                'eventId' => $eventId,
                'type' => ShuMeiCheckType::$TEXT_TYPE,
                'data' => [
                    'text' => $text,
                    'tokenId' => (string)$userId,
                ]
            ];
            $jsonLoad = json_encode($payLoad);
            $res = curlData($requestHost, $jsonLoad, 'POST', 'json');
            Log::info(sprintf("ShuMeiCheck filterText request:%s", $jsonLoad));
            Log::info(sprintf("ShuMeiCheck filterText response:%s", $res));
            $data = json_decode($res, true);
            if ($data && $data['code'] == 1100) {
                if ($data['riskLevel'] == 'PASS') {
                    return $text;
                } else if (isset($data['auxInfo']['filteredText'])) {
                    return $data['auxInfo']['filteredText'];
                } else {
                    return $text;
                }
            } else {
                return $text;
            }
        } catch (\Exception $e) {
            Log::error(sprintf("ShuMeiCheck filterText error errCode:%s errEx:%s", $e->getCode(), $e->getMessage()));
            return $text;
        }

    }

    /**
     * @info 图片检测
     * @param $filename string  图片地址
     * @param $eventId string 事件标识
     * @param $userId string 用户Uid
     * @return bool
     */
    public function imageCheck($filename, $eventId, $userId)
    {
        return true;
        try {
            $requestHost = 'http://api-img-bj.fengkongcloud.com/image/v4';
            $payLoad = [
                'accessKey' => $this->AccessKey,
                'appId' => $this->AppId,
                'eventId' => $eventId,
                'type' => ShuMeiCheckType::$IMAGE_TYPE,
                'data' => [
                    'tokenId' => (string)$userId,
                    'img' => CommonUtil::buildImageUrl($filename)
                ]
            ];
            $jsonLoad = json_encode($payLoad);
            $res = curlData($requestHost, $jsonLoad, 'POST', 'form-data', [], 10);
            Log::info(sprintf("ShuMeiCheck imageCheck request:%s", $jsonLoad));
            Log::info(sprintf("ShuMeiCheck imageCheck response:%s", $res));
            $data = json_decode($res, true);
            if ($data && $data['code'] == 1100 && $data['riskLevel'] == 'PASS') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::error(sprintf("ShuMeiCheck imageCheck error errCode:%s errEx:%s", $e->getCode(), $e->getMessage()));
            return true;
        }

    }

    /**
     * @info 音频流检测
     * @param $streamUrl string  图片地址
     * @param $eventId string 事件标识
     * @param $userId string 事件标识
     * @return mixed
     */
    public function audioStreamCheck($streamUrl, $eventId, $userId)
    {

        $requestHost = 'http://api-audiostream-bj.fengkongcloud.com/audiostream/v4';
        $payLoad = [
            'accessKey' => $this->AccessKey,
            'appId' => $this->AppId,
            'eventId' => $eventId,
            'type' => ShuMeiCheckType::$AUDIO_STREAM_STREAM_TYPE,
            'callback' => '',
            'data' => [
                'url' => $streamUrl,
                'tokenId' => (string)$userId
            ]
        ];
        $res = curlData($requestHost, json_encode($payLoad), 'POST', 'form-data');
        $data = json_decode($res, true);
        return $data;

    }

    /**
     * @info 阿里云音频文件检测
     * @param $filename string  图片地址
     * @return mixed
     * @throws
     */
    public function aliAudioCheck($filename)
    {

        AlibabaCloud::accessKeyClient(config('config.OSS.ACCESS_KEY_ID'), config('config.OSS.ACCESS_KEY_SECRET'))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        $uri = array('url' => CommonUtil::buildImageUrl($filename));
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->version('2018-05-09')
                ->pathPattern('/green/voice/syncscan')
                ->method('POST')
                ->options([
                    'query' => [

                    ],
                ])
                ->body(json_encode(
                    array(
                        'tasks' => array($uri),
                        'scenes' => array('antispam'),
                    )
                ))
                ->request();
            $result = $result->toArray();
            Log::info(sprintf("uri: %s checkResult:%s", $filename, json_encode($result)));
            if (!empty($result) && $result['code'] == 200) {
                $resultNormal = $result['data'][0]['results'][0]['details'][0]['label'] ?? '';
                $resultNormal2 = $result['data'][0]['results'][0]['label'] ?? '';
                if ($resultNormal == 'normal' || $resultNormal2 == 'normal') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (ClientException $e) {
            Log::info(sprintf("uri: %s ClientException:%s", $uri, $e->getMessage()));
            return false;
        } catch (ServerException $e) {
            Log::info(sprintf("uri: %s ServerException:%s", $uri, $e->getMessage()));
            return false;
        } catch (\Exception $e) {
            Log::info(sprintf("uri: %s Exception:%s", $uri, $e->getMessage()));
            return false;
        }
    }

    /**
     * @info 音频流检测开关
     * @return mixed
     */
    public function audioStreamCheckSwitch()
    {

        $redis = RedisCommon::getInstance()->getRedis();
        if (!$redis->exists(ShuMeiCheckType::$AUDIO_STREAM_CHECK_SWITCH)) {
            return 0;
        } else {
            return intval($redis->get(ShuMeiCheckType::$AUDIO_STREAM_CHECK_SWITCH));
        }

    }


    /**
     * @info 文本检测 将敏感词过滤成*
     * @param $text string  文本
     * @param $eventId string 事件标识
     * @param $userId int 用户uid
     * @return array
     */
    public function imCheckText($text, $eventId, $userId)
    {
        return [true, $text, null];
        try {
            $requestHost = 'http://api-text-bj.fengkongcloud.com/text/v4';
            $payLoad = [
                'accessKey' => $this->AccessKey,
                'appId' => $this->AppId,
                'eventId' => $eventId,
                'type' => ShuMeiCheckType::$TEXT_TYPE,
                'data' => [
                    'text' => $text,
                    'tokenId' => (string)$userId,
                ]
            ];
            $jsonLoad = json_encode($payLoad);
            $res = curlData($requestHost, $jsonLoad, 'POST', 'json');
            Log::info(sprintf("ShuMeiCheck imCheckText request:%s", $jsonLoad));
            Log::info(sprintf("ShuMeiCheck imCheckText response:%s", $res));
            $data = json_decode($res, true);
            if ($data && $data['code'] == 1100) {
                if ($data['riskLevel'] == 'PASS') {
                    return [true, $text, $res];
                } else if (isset($data['auxInfo']['filteredText'])) {
                    return [false, $data['auxInfo']['filteredText'], $res];
                } else {
                    return [false, $text, $res];
                }
            } else {
                return [false, $text, $res];
            }
        } catch (\Exception $e) {
            Log::error(sprintf("ShuMeiCheck imCheckText error errCode:%s errEx:%s", $e->getCode(), $e->getMessage()));
            return [true, $text, null];
        }

    }

    /**
     * @info 图片检测
     * @param $filename string  图片地址
     * @param $eventId string 事件标识
     * @param $userId string 用户Uid
     * @return array
     */
    public function imImageCheck($filename, $eventId, $userId)
    {
        return [true, $filename, null];
        $filename = CommonUtil::buildImageUrl($filename);
        try {
            $requestHost = 'http://api-img-bj.fengkongcloud.com/image/v4';
            $payLoad = [
                'accessKey' => $this->AccessKey,
                'appId' => $this->AppId,
                'eventId' => $eventId,
                'type' => ShuMeiCheckType::$IMAGE_TYPE,
                'data' => [
                    'tokenId' => (string)$userId,
                    'img' => $filename
                ]
            ];
            $jsonLoad = json_encode($payLoad);
            $res = curlData($requestHost, $jsonLoad, 'POST', 'form-data');
            Log::info(sprintf("ShuMeiCheck imImageCheck request:%s", $jsonLoad));
            Log::info(sprintf("ShuMeiCheck imImageCheck response:%s", $res));
            $data = json_decode($res, true);
            if ($data && $data['code'] == 1100 && $data['riskLevel'] == 'PASS') {
                return [true, $filename, $res];
            } else {
                return [false, $filename, $res];
            }
        } catch (\Exception $e) {
            Log::error(sprintf("ShuMeiCheck imImageCheck error errCode:%s errEx:%s", $e->getCode(), $e->getMessage()));
            return [true, $filename, null];
        }

    }

    /**
     * @info 阿里云音频文件检测
     * @param $filename string  图片地址
     * @return mixed
     * @throws
     */
    public function imAliAudioCheck($filename)
    {
        return [true, $filename, null];
        AlibabaCloud::accessKeyClient(config('config.OSS.ACCESS_KEY_ID'), config('config.OSS.ACCESS_KEY_SECRET'))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        try {
            $task1 = array('url' => CommonUtil::buildImageUrl($filename));
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->version('2018-05-09')
                ->pathPattern('/green/voice/syncscan')
                ->method('POST')
                ->options([
                    'query' => [

                    ],
                ])
                ->body(json_encode(
                    array(
                        'tasks' => array($task1),
                        'scenes' => array('antispam'),
                    )
                ))
                ->request();
            $result = $result->toArray();
            Log::info(sprintf("ShuMeiCheck imAliAudioCheck response:%s", json_encode($result)));
            if (!empty($result) && $result['code'] == 200) {
                if ($result['data'][0]['results'][0]['details'][0]['label'] == 'normal') {
                    return [true, $filename, json_encode($result)];
                } else {
                    return [false, $filename, json_ecnode($result)];
                }
            } else {
                return [false, $filename, json_encode($result)];
            }
        } catch (ClientException $e) {
            Log::error(sprintf("ShuMeiCheck imAliAudioCheck ClientException error errCode:%s errEx:%s", $e->getCode(), $e->getMessage()));
            return [true, $filename, null];
        } catch (ServerException $e) {
            Log::error(sprintf("ShuMeiCheck imAliAudioCheck ServerException error errCode:%s errEx:%s", $e->getCode(), $e->getMessage()));
            return [true, $filename, null];
        }

    }


    /**
     * @info 是否为免验证图片 true pass， false 需要验证
     * @param $message
     */
    public function checkNotAuthImage($message)
    {
        if (empty($message)) {
            return false;
        }
//        检测是否为闪萌图片
        $re = WeShineService::getInstance()->checkWeshineImages($message);
        if ($re) {
            return true;
        }
        return false;
    }

}