<?php

namespace app\utils;

use think\App;

class Error
{

    protected static $instance;
    protected $MsgFlags;

    const SUCCESS = 200;
    const ERROT = 500;
    const INVALID_PARAMS = 400;
    const MESSAGE_PARAMS_TYPE = 401;
    const API_SIGN_ERROR = 409;
    const USER_REQUEST_ERROR = 405;

    const IMAGES_ERROR_FAIL = 511;
    const ERROR_NOT_PARTNER_FAIL = 520;


    const ERROR_PHONE_CODE_FAIL = 10001;
    const ERROR_SEND_PHONE_CODE_FAIL = 10002;
    const ERROR_AUTH_IDEMPOTENT = 10055;
    const ERROR_AUTH_TOPIC_TAG = 10015;
    const ERROR_AUTH_PARTITION = 10016;

    const ERROR_SEND_PHONE_MORE_FAIL = 429;


    const ERROR_REQUEST_METHOD_FAIL = 20001;
    const ERROR_NOT_FIND_USER_FAIL = 20002;
    const ERROR_BLACK_USER_FAIL = 20009;
    const ERROR_CHECK_BLACK_FAIL = 20999;
    const ERROR_USER_LEVEL_FAIL = 20003;
    const ERROR_TEENAGERS_FAIL = 20029;
    const ERROR_TEEN_EXPIRE_FAIL = 20033;

    const ERROR_NOT_FIND_ROOM_FAIL = 20055;

    const ERROR_TOKEN_FATAL = 40000;
    const ERROR_FATAL = 50000;
    const ERROR_LOGIN_AUTH_FAIL = 501;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Error();
        }
        return self::$instance;
    }

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     *             return rjson([],Error::INVALID_PARAMS,Error::getInstance()->GetMsg(Error::INVALID_PARAMS));
     *
     */
    public function __construct()
    {
        $this->MsgFlags = $this->InitMsgFlags();
    }

    private function InitMsgFlags()
    {
        return [
            self::SUCCESS => "success",
            self::ERROT => "fail",
            self::INVALID_PARAMS => "请求参数错误",
            self::MESSAGE_PARAMS_TYPE => "消息类型错误",
            self::API_SIGN_ERROR => "api签名错误,请核对",
            self::USER_REQUEST_ERROR => "操作频繁,休息一下",

            self::IMAGES_ERROR_FAIL => "图片不合规",
            self::ERROR_NOT_PARTNER_FAIL => "您的灵魂伴侣在赶来的路上哦！",

            self::ERROR_PHONE_CODE_FAIL => "验证码错误",
            self::ERROR_SEND_PHONE_CODE_FAIL => "验证码发送失败",
            self::ERROR_AUTH_IDEMPOTENT => "频繁投递验签失败",
            self::ERROR_AUTH_TOPIC_TAG => "验证topic失败",
            self::ERROR_AUTH_PARTITION => "验证partition失败",
            self::ERROR_SEND_PHONE_MORE_FAIL => "发送验证码超过限制,请稍后重试",


            self::ERROR_REQUEST_METHOD_FAIL => "请求类型错误",
            self::ERROR_NOT_FIND_USER_FAIL => "用户不存在",
            self::ERROR_BLACK_USER_FAIL => "用户被封禁",
            self::ERROR_CHECK_BLACK_FAIL => "当前用户被封禁", //当前用户被封禁，封号后登陆异常
            self::ERROR_USER_LEVEL_FAIL => "用户等级不存在",
            self::ERROR_TEENAGERS_FAIL => "青少年模式宵禁22点-6点不能登陆",
            self::ERROR_TEEN_EXPIRE_FAIL => "青少年模式已经到期不能访问",

            self::ERROR_NOT_FIND_ROOM_FAIL => "房间不存在请检查",

            self::ERROR_TOKEN_FATAL => "token无效,请重新登陆",
            self::ERROR_FATAL => "未知错误",
            self::ERROR_LOGIN_AUTH_FAIL => "非您常用设备，登录请再次验证",
        ];
    }

    /**
     * @param $s
     * @param $suffix
     * @return false
     */
    public function GetMsg(int $code)
    {
        if (isset($this->MsgFlags[$code])) {
            return $this->MsgFlags[$code];
        }
        return $this->MsgFlags[self::ERROT];
    }

    public function printAll()
    {
        echo "<pre>";
        print_r($this->MsgFlags);
        echo "<pre/>";
    }
}
