<?php

use think\facade\Env;

/**
 * Created by PhpStorm.
 * User: sh
 * Date: 2019/7/24
 * Time: 9:42
 */
return array(

    'APP_URL_image' => 'http://like-game-1318171620.cos.ap-beijing.myqcloud.com/',   //域名地址cos
    'APP_URL_image_two' => 'http://like-game-1318171620.cos.ap-beijing.myqcloud.com/',   //域名2地址cos
    'WEB_URL' => '',    //域名地址
    'OSS' => [
        "ACCESS_KEY_ID" => '',      ////阿里云OSS  IDLTAI4G1bUCQXqwjG8qo91u3b
        "ACCESS_KEY_SECRET" => '',        //阿里云OSS 秘钥
        "ENDPOINT" => 'oss-cn-beijing.aliyuncs.com',            //阿里云OSS 地址
        "BUCKET" => 'yinka-resource',                       //oss中的文件上传空间
    ],
    'redis' => [
        // 驱动方式
        'type' => 'redis',
        'host' => Env::get('redis.hostname', '127.0.0.1'),
        'port' => Env::get('redis.port', 6379),
        'password' => Env::get('redis.password', ''),
    ],
    //短信验证码
    'ALISMS' => [
        'accessKeyId' => '',
        'accessSecret' => '',
        'ali_sms_regionId' => 'cn-hangzhou',
        'ali_sms_signName' => '佳年互娱',
        'ali_sms_templateCode' => 'SMS_254750890'
    ],

    //短信验证码
    'tencent_sms' => [
        "ACCESS_KEY_ID" => '',
        "ACCESS_KEY_SECRET" => '',
        "ENDPOINT" => 'sms.tencentcloudapi.com',
        "Region" => 'ap-beijing',
        "SignName" => 'like电竞App',
        'SmsSdkAppId' => '1400824964',
        'TemplateId' => '1812772',
    ],

    'VERTIFYCODE' => true,
    'ALIGREEN' => [
        'AccessKeyID' => '',
        'AccessKeySecret' => '',
    ],
    'THIRDLOGIN' => [
        2 => ['app_id' => '102055488',
            'app_secret' => '',
            'scope' => 'get_user_info',],
        3 => ['app_id' => 'wx8996aada32e3773a',
            'app_secret' => '',
            'scope' => 'snsapi_base'],
    ],

    'THIRDLOGIN1' => [
        'app_id' => '',
        'app_secret' => '',
        'scope' => 'snsapi_base'
    ],

    //阿里云oss地址
    'ALIYUNURL' => 'http://like-game-1318171620.cos.ap-beijing.myqcloud.com/',
    //STS访问oss检测配置
    'STSCONF' => [
        "AccessKeyID" => "LTAI5tAwKEs4qjyddQai4fz6",
        "AccessKeySecret" => "",
        "RoleArn" => "acs:ram::1065065656900457:role/ramosssts",
        "BucketName" => "yinka-resource",
        "Endpoint" => "oss-cn-beijing.aliyuncs.com",
        "TokenExpireTime" => "3600",
        "PolicyFile" => '{
                        "Statement": [
                            {
                              "Action": [
                                "oss:*"
                              ],
                              "Effect": "Allow",
                              "Resource": ["acs:oss:*:*:*"]
                            }
                          ],
                        "Version": "1"
                }',
    ],

//支付宝支付配置
    'ALIPAY' => [
        'app_id' => '2018053160274419',
        'subject' => 'MUA语音',
        'partner' => '2088131391400150',
        'service' => 'mobile.securitypay.pay',
        'input_charset' => 'utf-8',
        'seller_id' => '',
        'transport' => 'http',
        'notify_url' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/paymentnotify',
        'http_verify_url' => 'http://notify.alipay.com/trade/notify_query.do?',
        'https_verify_url' => 'https://mapi.alipay.com/gateway.do?service=notify_verify&',
        'private_key' => '',
        'return_url' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/paymentreturn',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAj+/n84hpdreCWrnnzSY0WYBeSS0HmMvhO7xLbHhGDq1VUEkvrWoDfjUqL5u7REm8p/IH84TMVCpHiNbB4Ny6fOWA9FhAN8aLzFAyQu0BlNfegGLuYDFKs6oAA/ztZEyinF6k1KaECo9P3j+RC4Uu1Svxv9C2GRD/Z7AsiwSjWPF+N9PN+1tkRZXDgWdPNm6iRi8vnjHjFnKyFPBfkuZVNHfK0wwYak46pjAI97AljRN/zGRbBfuFTIa97xhjils83JlQ1qp9xL29FnW2OE+BrAlDi/B+la7gQurkz8pD91mLgXOtCvgOUM7PTFBPsB4X1zYnFVt8A/N1dBgt3yvxHQIDAQAB',
        'log' => '/tmp/newalipay.log'
    ],

    //支付宝原生
    'alipay_yuansheng'=>[
        'app_id'=>'2021002110683957',
        'notify_url'=>'http://php-api.takecares.cn/api/v1/appalinotify',
        'redpacket_notify_url' => 'http://php-api.takecares.cn/api/v1/api/v1/alipackets',
        'return_url'=>'',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiYoJhEhuDVHHTaurOFcp02LkBuxLfCAQhGHEIZffh0JWgkFvXgeaLceD2YbwaXZAbQwUC0qr+n7+456Er0mU737a5NZ6TeYf7kRXOeiIWDTfpYZtEHm4HCNBLtwKIFzjpR/O/bpxe3KFa77+NHuhqZ5f2oRaaUWndl4nT7NzUgz8ZLtag9/bQ+QGkD/xwDL1gm6OyJR8P67Uoa5HgKReZ+dM58lgvzqSfVo49mdhhjaXq36mKe2ciCUdNAlcbKOzdFQK1dUSVG/SjRRd8Y6yoGaKhD/oBRYyqr+IKkE1H+gN6k5nTWJ2ZhF7PploncYEzSBc3/6dqhvChPSuPAYltQIDAQAB',
        'private_key' => '',
        'log'=>'/tmp/alipay_yuansheng.log',
        'PARTNER'=> '2088731529236563',
        'vip_notify_url'=>'http://php-api.takecares.cn/api/v1/appvipalinotify',
        'sign_notify_url'=>'http://php-api.takecares.cn/api/v1/autoSignAliNotify',
    ],


    'alipay_yuansheng1'=>[
        'app_id'=>'2021003196666132',
        'notify_url'=>'http://php-api.takecares.cn/api/v1/appalinotify',
        'redpacket_notify_url' => 'http://php-api.takecares.cn/api/v1/api/v1/alipackets',
        'return_url'=>'',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjiBYEadjP0nbpCBY7nMKHb4PczGSaYAzDpzJ+6bWGOeTVK+9wecaXV2ISvl4RcD28BurWUQU/pGpeqjNSVA6WMlEglTAuqNkNTRgdBMW6RohtCo1aPVAVr4/PW4wZyh/gGhmnl7a/Pr/rmsZ3eFvrTyhE8YDJvm1MwUHj2sntsCY4sbFyhb5T+tchWGM60g9yKfgT0Outw1aCTE+2U/gvJ3Fg4gt2kBpNuNY4fxaTs344XCrEvCqVi6prlJa5XxPYWnZwGcrctKChChH76x0uTPtSGNVfgesSVB9vpP0RnPBlBLz3uA22J3yHNuZXbmzZlS/3sEqlzIJO44sBxV2awIDAQAB',
        'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCLkYghpSJiRcJxKT3jSUXPknoU2O5mPdjbp8Xdh7SqU91CspHXLTxxw5Jz1DXaj2HuiJAFMa6NDnv7BumTPydarWZu2x/gqtIa7po9i7v2ELEnbFXfElu+vMEIE+Q0FB6c23Vvd/PMFR+fOZwWlTedyXJWDmF7BUOtGsnRblmkLin15Vux+bsbdhLTV7fkOqVWVTnCeVWHnt8hzjTghi8zabKHlYZhFXZ9jWBt5VQXsZvzCMSFMEywb5kDNEvk5kqKZve0GkKOkC9EhSfDsGZK0dZ7fubgGiTcAOvwKmo/+vxNtjgzLOsqQMF//VDAw6qjtwt5fJh9j4yaDWlMnIarAgMBAAECggEAIlVowa4X6Ujz7laQ7OrHi6qi8aHz857fTBnXqQmLPiNnWNMI4YA1UF8mFexsWxnOo5lgpIZ2RCj+AuDOcPSmP75MZpTtIJ2lFg8AToejc4LjsakK7tdbTm6spcoO52jTpw6tswA1L006/DZ12XBXwC8gO19KR7Mh1OG0KBsXXjXEd2Ywx229bjdIJZri5uzKpbOKhrwTGIMbyUpSUeeEH7R9DponsojrKBaD20sHopzDHYE5baTgP9T/sp3VR4FIg1jcMrAb8ZRfwjk/HboI3uqrzqpm4uHojN1enxa4u5W3nAc6yBXs5rsgJ+lOPv9ZFUBckmY8wjuzaIrECU/C0QKBgQD5IiG0r6E0HlmhqmaNsJ2rK4pt/DmGEaVRJ7SprkJ4+h1JkK+XDdMgdC+BRaGHKoHAVGb6mNBwd2bpJ5UHiXlb0Uw2XnwMNiHmCRbGx/nJUKRRHmdcal+p5qE2wWjqF8dJlmaHC0hJsPIqjzk6Va/50coUbwufPI77zAGoOvXWTQKBgQCPalFaYi7IqiX2sAYFjlmoSaBExHYWVcTlNZy92LXA+GhQJO2FLfczsLxaKJF/SMUwBxwjDcdkfAXudW7OYyI83n5SwsPNtVFFGBm5EGazcltWBfHyWeVcxrkM/F3Y6lScrh61Y/e7uif1Pq0wCsMGRVeslpuK408sCIqFtlm81wKBgQDUKSfxGrw6iTolfdrWATlUYsEBhxFpxi813I1zDu6W3dEBLBEMn35Tnf4ypZy4Yg+bPYVxFaA0lspx9f5pK77I3YV8q5wSPitCHi1iXTywH1e/qRe20PPk2X4jBjSVXmidl1J64LXP47tnWQ8QSZaSNgFUw3hvRAA0GHi0znk65QKBgFQ68jrubWHHpPJk4bSDrZ7MV0fsRxrJFxIz0bIixGTowINJnQLaQ1TlmUouh33FZKLXmivwXMpkmSs7Z2/qA2LSnkjHQS7hLjExfXIW8uqz4Hb+mOJo7+/0exzoX8oVnspC7aBFbWuhYvSD8j3EJFTbhynDbuk8pfRLs+fieIQTAoGALJbEbE9tr9K0aKYHKyscA9fEHsKPrQP8k+l8DmRRJ5jwWUxke5/dYUcyL+QN1oQdFzvfyUcI1eSDEdAYHerJbxzUKDILKta8n3MgDkgIxlAwDx5RyB6Kpz1dAdhfQruR/QUHGKgdMeJKD0b1/EI3iNxFJgCOyomVeXDrgSc+5xw=',
        'log'=>'/tmp/alipay_yuansheng.log',
        'PARTNER'=> '2088641263155711',
        'vip_notify_url'=>'http://php-api.takecares.cn/api/v1/appvipalinotify',
        'sign_notify_url'=>'http://php-api.takecares.cn/api/v1/autoSignAliNotify',
    ],


    'zeGo' => [
        'appId' => '',
        'secret' => '',
    ],

// ------------ start -------------
    'wechat_yuansheng' => [
        'appid' => 'wx8996aada32e3773a', //
        'app_id' => 'wx34d93ac79d4e3d72',//公众号id
        'miniapp_id' => '',//小程序id
        'mch_id' => '1646017862',  //
        'key' => '04316b19f32fcc1d13818ea20891f1db',
        'notify_url' => 'http://php-api.takecares.cn/api/v1/appwxnotify',
        'log' => '/tmp/weixin_yuansheng.log',
        'redpacket_notify_url' => 'http://php-api.takecares.cn/api/v1/wxpackets',
    ],

    'alipay_yinlian' => [
        'MSGSRC' => 'WWW.SCRQKJI.COM',
        'MID' => '898440048161750',
        'TID' => '99114105',
        'NUM' => '7266',
        'NOTIFY_URL' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/appaliyinliannotify',
        'MSGTYPE' => 'trade.precreate',
        'PARTNER' => 2088731855039665,
    ],
    'wechat_yinlian' => [
        'MSGSRC' => 'WWW.SCRQKJI.COM',
        'MID' => '898440048161750',
        'TID' => '99114105',
        'NUM' => '7266',
        'NOTIFY_URL' => 'http://gzh.muayuyin.com/index.php/Api/WechatPublic/wxBackNew',
        'MSGTYPE' => 'wx.appPreOrder',
    ],

//微信公众号配置
    'WECHAT_OPEN' => [
        'APPID' => 'wx34d93ac79d4e3d72',
        "MCHID" => '1646017862',
        'APIKEY' => '',
        'APPSECRET' => '',
        'PLACE_ORDER' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
        'notify_url' => 'http://php-api.takecares.cn/api/v1/appwxnotify',
        'payment' => 'http://php-api.takecares.cn/api/v1/payment',
    ],

//微信web支付
    'WXWEBPAY' => [
        'appid' => 'wxe50b8b85af03082a',
        'app_id' => 'wx4aaa37e307a8b9fa',
        'mch_id' => '1543515161',
        'notify_url' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/wxpnotify',
        'log' => '/tmp/weixinweb.log',
        'key' => '5c4a81648ccc8342ce21bbd8c5e590f8'
    ],
    'amq' => [
        'host' => '172.17.213.39',
        'port' => 5672,
        'user' => 'admin1',
        'pwd' => 'admin1',
        'prefix' => 'test',
    ],
//渠道号
    'CHANNELLIST' => [
        'zhangyue' => 1 //掌阅
    ],
//渠道投放
    'REGISTCHANNEL' => [
        'yidong01' => 1,
        "yidong02" => 2,
        "yidong03" => 3,
        "yidong04" => 4,
        "yidong05" => 5,
        "yidong06" => 6,
        "yidong07" => 7,
        'yidong08' => 8,
        "yidong09" => 9,
        "yidong10" => 10,
        "yidong11" => 11,
        "yidong12" => 12,
        "yidong13" => 13,
        "yidong14" => 14,
        "yidong15" => 15,

    ],
    'yunxin' => [
        "Appkey" => 'b9a9a8dbf51cc7163ac6676ceb2439ad',
        "Appsecret" => '',
    ],
//消息url

    'socket_url' => 'http://pyapi.takecares.cn/iapi/broadcast',
    'socket_url_base' => 'http://pyapi.takecares.cn/',

    'getui'=> [
        'appid' => 'AKmaiLoayV9IPLW9wto5O3',
        'appkey' => '',
        'mastersecret' => '',
        'host' => 'http://sdk.open.api.igexin.com/apiex.htm',
        'loginurl'=>'https://openapi-gy.getui.com/v1/gy/ct_login/gy_get_pn',
    ],

    'yunxin_prefix' => 'test_',
    'user_online_single_url' => 'http://182.92.186.104:9081/api/query/online',
    'user_online_all_url' => 'http://182.92.186.104:9081/api/query/device',
    'fq_assistant' => '1056232',
    'service_customer' => '1056232',
    'gift_box_id' => '376',

    'scale' => 1000,    //豆兑换钻石比例 1:1000
    'khd_scale' => 10000,//钱兑换钻石 1:10000
    'bean_coin_scale' => 100,//豆兑换金币 1:100
    'self_scale' => 0.6,//个人比例
    'coin_scale' => 10,//钱兑换豆 1:10

    'duobao' => 'https://ts.api.qqyy.jmhuyu.com/build/web-mobile/index.html?mtoken=',

    'smsother' => [
        'appkey' => '8X603i',
        'appsecret' => '',
        'appcode' => '1000',
        'host' => 'http://39.97.4.102:9090/sms/batch/v1',
    ],

    'exchange_gift' => [
        'public_screen' => [397],
        'float_screen' => [396, 372, 231]
    ],
    'shumei' => [
        'AccessKey' => '',
        'AppId' => 'default',
        'audioStreamSwitch' => 0
    ],
    'heartInterval' => 20,//用户心跳间隔时间
    'threeLoot' => 'https://ts.api.qqyy.jmhuyu.com/sanrenduobao?mtoken=',
    'baoxiang' => 'http://php-api.takecares.cn/baoxiang?mtoken=',
    'zhuanpan' => 'http://php-api.takecares.cn/zhuanpan?mtoken=',
    'taojin' => 'https://ts.api.qqyy.jmhuyu.com/tjzl?mtoken=',
    'dadishu' => 'https://ts.api.qqyy.jmhuyu.com/dadishu?mtoken=',
    'niudanji' => 'https://ts.api.qqyy.jmhuyu.com/niudanji?mtoken=',
    'kwfilter' => [
        'url' => 'http://kw.fanqie.com:180/kwfilter'
    ],
    'sendOneself' => false, //用户是否可以自己送自己礼物
    'orderExpire' => 300, //最小为300s即5min
    'queueKey' => 'queueList', //队列key

    'EncryptDriver' => "off",   // enbale:"enable"  close:"off"
    'EncryptKey' => "==",   //encryptKey  打乱后的假值：下发假值，逆向为真值用
    'EncryptKeySecond' => "==",   //encryptKey  error
    'apiSignAuthKey' => "",   //api auth sign key
    'apiSignEnable' => "enable",   //api sign auth "enable"// "enable": "off"
    'apiAuthTimestamp' => 'off', // enbale:"enable"  close:"off"
    'apiSignSalt' => "",
    'ossUrl' => 'http://image2.fqparty.com',
    'appDev' => Env::get('APPDEV', "dev"),  // 测试:"dev"  正式:"online"
    'firstPayUrlIndex' => 'https://ts.api.qqyy.jmhuyu.com/shouchong?mtoken=',
    'firstPayUrlRoom' => 'https://ts.api.qqyy.jmhuyu.com/firstpay?mtoken=',
    'firstChargeUrlIndex' => "http://test.activity.muayuyin.com/activity/shouchong2/index.html",
    'baseUrl2' => [
        'online_url' => [
            'api_url' => ['http://php-api.takecares.cn'],
            'py_url'  => ['http://pyapi.takecares.cn'],
            'ws_url'  => ['http://pyapi.takecares.cn/ws'],
        ],
        'test_url' =>  [
            'api_url' => ['http://php-api.takecares.cn'],
            'py_url'  => ['http://pyapi.takecares.cn'],
            'ws_url'  => ['http://pyapi.takecares.cn/ws'],
        ],
    ],

    'ampq_recall' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_member_recall',
        'exchange' => 'ex_member_recall',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],

//    ampq_recall_user_push
    'ampq_recall_user_push' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_member_recall_user_push',
        'exchange' => 'ex_member_recall_user_push',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],

    // mq
    'ampq_im_message' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_im_message',
        'exchange' => 'ex_im_message',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],

    'ampq_login_detail_message' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_login_detail_message',
        'exchange' => 'ex_login_detail_message',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],


    'ampq_elastic_room' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_message_bus_elastic_room',
        'exchange' => 'ex_message_bus',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],
    'ampq_elastic_user' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_message_bus_elastic_user',
        'exchange' => 'ex_message_bus',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],

    'ampq_message_bus' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'routing_key' => "ex_message_bus",
        'queue_name' => '',
        'exchange' => 'ex_message_bus',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],

    'ampq_user_register_referee' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_user_register_referee',
        'exchange' => 'ex_user_register_referee',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],

    // 特别关心
    'ampq_user_special_care' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_user_special_care',
        'exchange' => 'ex_user_special_care',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => Env::get('rabbitmq.user', 'root'),
        'pass' => Env::get('rabbitmq.password', 'root'),
    ],


    // es
    'es_host' => env('es.host', '172.31.48.10:9200'),

    'open_first_charge' => false,  // 是否开启首充 true开启，false关闭

    // 三方支付 - 银联商务
    'chinaaums' => [
        'msgSrc' => 'WWW.SCRQKJI.COM',            //消息来源(msgSrc)
        'msgSrcId' => '7266',        // 来源编号（msgSrcId）
        'mdKey' => 'JjeryZKTzMAtXdXNybBTTHTd724MdpjRmA2XjCsNX2FX2p8w',            // 通讯密钥
        'mid' => '89844014816ABFD',            //商户编号
        'tid' => 'HDB3ML42',    //终端号
        'appid' => 'wx7c1049096711ca12',  // 微信小程序 appid
        'appSecret' => 'b0974fe842e7f4e0b3dc9a5361f3b3e8',  // 微信小程序 秘钥
        'notify_url' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/chinaumsNotify', // 支付成功回调地址
        'return_web_url' => 'https://test.php.fqparty.com/gw/#/topup',
        'return_gzh_url' => 'https://test.php.fqparty.com/gw/#/topup',
        'mid_list' => [
            ['mid' => '89844014816ABFD', 'tid' => 'HDB3ML42'],  //商户编号  //终端号
            ['mid' => '89844014816ABFC', 'tid' => 'YJ6HBGYE'],
            ['mid' => '89844014816ABFB', 'tid' => 'DH6E23C3'],
            ['mid' => '89844014816ABEZ', 'tid' => 'C4HW6LN3'],
            ['mid' => '89844014816ABFA', 'tid' => 'J0GVZYD9'],
            ['mid' => '89844014816ABEY', 'tid' => 'THTRNG2S'],
            ['mid' => '89844014816ABEN', 'tid' => 'K4SLDX6A'],
            ['mid' => '89844014816ABFH', 'tid' => 'UH16JQUL'],
        ]
    ],
    'sensorsData' => [
        'log_agent_path' => '/www/wwwroot/sensorsLog/service_log',
        'sa_server_url' => 'https://yinlianyuyin.datasink.sensorsdata.cn/sa?token=e849cf0934843185&project=default',
        'switch' => false
    ],
    'wechat_pay_channel_way' => 1,  // 微信支付接入使用方式  1:使用原生   2:使用银联商务
    'ali_pay_channel_way' => 1,      // 阿里支付接入使用方式  1:使用原生   2:使用银联商务

    'apple_subscription_password' => '', // 苹果密钥

    'old_ios_show_new_vip' => true,

    // 玩么赛事
    "WanMoGame" => [
        "token" => "qhrEDE9URnH3cTj4",
        "secret" => "B4BMzQ3UAQED0Y3JwDn90nVaGQqNmWNTxC6H2c7SGZMhugTqNpR87p95qY5XK6eD",
    ],

    // 支付内容显示
    "pay_subject" => "like电竞,扫码刷单是骗局",

    // IP ipdatacloud KEY
    "ip_cloud_key" => "eb5ec4132edc11ee8ac100163e25360e",

    "write_user_list" => [
        "1097753","1078990"
    ],

    "din_pay" => [
        "merchant_code" => "117002020019",
        "sub_merchant_code" => "117002020020",
        "notify_url" => "http://php-api.takecares.cn/api/v1/dinNotify",
        "client_ip" => Env::get('ip.addr', "127.0.0.1"),
    ]
);

