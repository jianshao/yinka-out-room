<?php

namespace app\domain\chuanglan\service;

use app\domain\chuanglan\api\ChuanglanSmsApi;
use think\facade\Log;

//创蓝短信
class ChuanglanService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChuanglanService();
        }
        return self::$instance;
    }


    /**
     * @Info 发送短信
     * @param $phone
     * @param $msg
     * @return array|mixed
     * @example:  $msg = '【音恋语音】您好！验证码是:' . $code;
     *            $phone = '15300000000';
     */
    public function sendSMS($phone, $msg)
    {
        $clapi = new ChuanglanSmsApi();
        $result = $clapi->sendSMS($phone, $msg);
        Log::INFO(sprintf("ChuanglanService sendSMS param phone:%s,msg:%s response:%s", $phone, $msg, $result));
        $output = json_decode($result, true);
        if (empty($output)) {
            return [];
        }
        if (isset($output['code']) && $output['code'] == '0') {
            return $output;
        }
        return [];
    }


    /**
     * @info 发送变量短信
     * @param $msg
     * @param $params
     * @return array|mixed|string[]
     *
     * @example: $msg = '【253云通讯】您好，您的验证码是{$var},请在{$var}分钟内完成注册，感谢您的使用。';
     *           $params = '18300000000,153642,5;18900000000,154789,5;15333333333,458753,5';
     * apiresponse:
     * array:6 [
     * "code" => "0"
     * "failNum" => "0"
     * "successNum" => "1"
     * "msgId" => "21091517304600202201000009203565"
     * "time" => "20210915173046"
     * "errorMsg" => ""
     * ]
     *
     */
    public function sendVariableSMS($msg, $params)
    {
        if (config("config.appDev") === "dev") {
            return [
                'code' => "0",
                'failNum' => "0",
                'successNum' => "1",
                'msgId' => "21091517304600202201000009203565",
                'time' => "20210915173046",
                'errorMsg' => "",
            ];

//            return [
//                'code' => "404",
//                'failNum' => "0",
//                'successNum' => "1",
//                'msgId' => "21091517304600202201000009203565",
//                'time' => "20210915173046",
//                'errorMsg' => "",
//            ];
        }
        $clapi = new ChuanglanSmsApi();
        $result = $clapi->sendVariableSMS($msg, $params);
        Log::INFO(sprintf("ChuanglanService sendVariableSMS msg:%s param:%s response:%s", $msg, $params, $result));
        $output = json_decode($result, true);
        if (empty($output)) {
            return [];
        }
        if (isset($output['code']) && $output['code'] == '0') {
            return $output;
        }
        return [];
    }


    /**
     * @Info 发送营销短信
     * @param $phone string 手机号
     * @param $name string 昵称
     * @return array|mixed
     */
    public function sendMarketing($phone, $name)
    {
        if (empty($phone)) {
            return [];
        }
        $msg = '【音恋语音】{$var}，您在音恋语音收到新的留言，快去看看吧～点击查看：rongqii.cn 退订回T';
        $param = sprintf("%s,%s;", $phone, $name);
        return $this->sendVariableSMS($msg, $param);
    }


    /**
     * @Info 查询余额
     * @return mixed|string
     */
    public function queryBalance()
    {
        $clapi = new ChuanglanSmsApi();
        $result = $clapi->queryBalance();
        Log::INFO(sprintf("ChuanglanService queryBalance response:%s", $result));
        return $result;
    }


}