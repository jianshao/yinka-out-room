<?php

namespace app\common;

use think\facade\Request;
use constant\CodeConstant as coder;
use Green\Request\V20180509 as Green;
use Green\Request\Extension\ClientUploader;
use DefaultProfile;
use DefaultAcsClient;
use think\facade\Log;

date_default_timezone_set('Asia/Shanghai');

class TextcanimgCommon
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TextcanimgCommon();
        }
        return self::$instance;
    }

    /**图片检测
     * @param $url array  URL地址
     * @return string
     * @throws \ClientException
     * @throws \ServerException
     */
    public function checkImg($url)
    {
        //OSS检测图片
        $ossurl = config('config.ALIYUNURL');
        $stsConf = config('config.STSCONF');
        $accessKeyID = $stsConf['AccessKeyID'];
        $accessKeySecret = $stsConf['AccessKeySecret'];
        $roleArn = $stsConf['RoleArn'];
        $tokenExpire = $stsConf['TokenExpireTime'];
        $policy = $stsConf['PolicyFile'];
        $iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
        DefaultProfile::addEndpoint("cn-shanghai", "cn-shanghai", "Green", "green.cn-shanghai.aliyuncs.com");
        $client = new DefaultAcsClient($iClientProfile);
        $request = new Green\ImageSyncScanRequest();
        $request->setMethod("POST");
        $request->setAcceptFormat("JSON");
        if (empty($url)) {
            return false;
        } else {
            foreach ($url as $key => $value) {
                $task1[] = array(
                    'dataId' => uniqid(),
                    'url' => $ossurl . $value
                );
            }
        }
        $request->setContent(json_encode(array("tasks" => $task1, "scenes" => array("porn", "terrorism"), "bizType" => "msg")));
        try {
            $response = $client->getAcsResponse($request);
            Log::info("返回image:" . json_encode($response, true));
            if (200 == $response->code) {
                $taskResults = $response->data;
                foreach ($taskResults as $taskResult) {
                    $taskId = $taskResult->taskId;
                    Log::info("内容安全_taskId：" . $taskId);
                    if (200 == $taskResult->code) {
                        $sceneResults = $taskResult->results;
                        foreach ($sceneResults as $key => $sceneResult) {
                            $scene = $sceneResult->scene;
                            $suggestion = $sceneResult->suggestion;
                            if ($suggestion == 'block') {
                                return $key;
                            }
                        }

                    } else {
                        return false;
                    }
                }
                return -1;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::error(sprintf("checkImg error:%s,strace:%s", $e->getMessage(), $e->getTraceAsString()));
            return false;
        }
    }


    /**
     * @info 图片检测 过滤异常图片,仅返回通过的
     * @param $url
     * @return array  [结果 int,图片数组 array]  结果：-1为成功， 0为异常  图片数组：成功的图片arr 全部失败则为空
     */
    public function checkImgReset($url)
    {
        if (empty($url)) return [0, []];
        //OSS检测图片
        $ossurl = config('config.ALIYUNURL');
        $stsConf = config('config.STSCONF');
        foreach ($url as $key => $value) {
            $task1[] = array(
                'dataId' => uniqid(),
                'url' => $ossurl . $value
            );
        }
        $accessKeyID = $stsConf['AccessKeyID'];
        $accessKeySecret = $stsConf['AccessKeySecret'];
        $iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
        DefaultProfile::addEndpoint("cn-shanghai", "cn-shanghai", "Green", "green.cn-shanghai.aliyuncs.com");
        $client = new DefaultAcsClient($iClientProfile);
        $request = new Green\ImageSyncScanRequest();
        $request->setMethod("POST");
        $request->setAcceptFormat("JSON");
        $request->setContent(json_encode(array("tasks" => $task1, "scenes" => array("porn", "terrorism"), "bizType" => "msg")));
        try {
            $response = $client->getAcsResponse($request);
            Log::info("返回image:" . json_encode($response, true));
            if (200 !== $response->code) {
                return [0, []];
            }
            $taskResults = $response->data;
            $markErr = false;
            foreach ($taskResults as $taskKey => $taskResult) {
                if (200 !== $taskResult->code) {
                    $markErr = true;
                    unset($url[$taskKey]);
                    continue;
                }
                $sceneResults = $taskResult->results;
                foreach ($sceneResults as $key => $sceneResult) {
                    $suggestion = $sceneResult->suggestion;
                    if ($suggestion === 'block') {
                        $markErr = true;
                        unset($url[$taskKey]);
                    }
                }
            }
            if ($markErr === true) {
                return [0, $url];
            }
            return [-1, $url];
        } catch (\Exception $e) {
            Log::error(sprintf("checkImg error:%s,strace:%s", $e->getMessage(), $e->getTraceAsString()));
            return [0, []];
        }
    }


}


?>