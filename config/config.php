<?php

use think\facade\Env;

/**
 * Created by PhpStorm.
 * User: sh
 * Date: 2019/7/24
 * Time: 9:42
 */
return array(

    'APP_URL_image' => 'http://resource.ddyuyin.com/',   //域名地址oss
    'APP_URL_image_two' => 'http://resource.ddyuyin.com/',   //域名2地址oss
    'WEB_URL' => '',    //域名地址
    'OSS' => [
        "ACCESS_KEY_ID" => 'LTAI5tAwKEs4qjyddQai4fz6',      ////阿里云OSS  IDLTAI4G1bUCQXqwjG8qo91u3b
        "ACCESS_KEY_SECRET" => 'L9bkb1ni0xJ6w4KMDiXhMJdYtxWB0Y',        //阿里云OSS 秘钥
        "ENDPOINT" => 'oss-cn-beijing.aliyuncs.com',            //阿里云OSS 地址
        "BUCKET" => 'yinka-resource',                       //oss中的文件上传空间
    ],
    'redis' => [
        // 驱动方式
        'type' => 'redis',
        'host' => Env::get('redis.hostname', 'r-2zep27hvk4ys3nypqu.redis.rds.aliyuncs.com'),
        'port' => Env::get('redis.port', 6379),
        'password' => Env::get('redis.password', 'nPyOOousxrIT7IQq'),
    ],
    //短信验证码
    'ALISMS' => [
        'accessKeyId' => 'LTAI5tQtFAbBc89h5dqLBTt5',
        'accessSecret' => 'un5CcjysGlbG7lqBY2sOnzJLSmnBUn',
        'ali_sms_regionId' => 'cn-hangzhou',
        'ali_sms_signName' => '佳年互娱',
        'ali_sms_templateCode' => 'SMS_254750890'
    ],
    'VERTIFYCODE' => true,
    'ALIGREEN' => [
        'AccessKeyID' => 'LTAI4G1bUCQXqwjG8qo91u3b',
        'AccessKeySecret' => 'l1TZsfl9jls6OtjjsA4MFms7bRLPBV',
    ],
    'THIRDLOGIN' => [
        2 => ['app_id' => '102028850',
            //'app_secret'    => 'F6F82C966F1482A1949A8EA2EF9B015B',
            'app_secret' => 'OeWBhy5yiDblnbFg',
            'scope' => 'get_user_info',],
        3 => ['app_id' => 'wxeadb1acd6ff08be1',
            'app_secret' => '025d749f70488896af6abb8ae13a53d3',
            'scope' => 'snsapi_base'],
    ],

    'THIRDLOGIN1' => [
        'app_id' => 'wxc4c6c84a8629a530',
        'app_secret' => '0e7ea5ff7e23d2730ec694b869d5d01d',
        'scope' => 'snsapi_base'
    ],

    //阿里云oss地址
    'ALIYUNURL' => 'http://resource.ddyuyin.com/',
    //STS访问oss检测配置
    'STSCONF' => [
        "AccessKeyID" => "LTAI5tAwKEs4qjyddQai4fz6",
        "AccessKeySecret" => "L9bkb1ni0xJ6w4KMDiXhMJdYtxWB0Y",
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
        'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCP7+fziGl2t4JauefNJjRZgF5JLQeYy+E7vEtseEYOrVVQSS+tagN+NSovm7tESbyn8gfzhMxUKkeI1sHg3Lp85YD0WEA3xovMUDJC7QGU196AYu5gMUqzqgAD/O1kTKKcXqTUpoQKj0/eP5ELhS7VK/G/0LYZEP9nsCyLBKNY8X430837W2RFlcOBZ082bqJGLy+eMeMWcrIU8F+S5lU0d8rTDBhqTjqmMAj3sCWNE3/MZFsF+4VMhr3vGGOKWzzcmVDWqn3Evb0WdbY4T4GsCUOL8H6VruBC6uTPykP3WYuBc60K+A5Qzs9MUE+wHhfXNicVW3wD83V0GC3fK/EdAgMBAAECggEACcROqfDEr0COgNeCiiIghT43p6F9lXmoI+SH/ak/n7lVQ6hjqtG5wPOclDRuBZk3SvIaZgTJ1KA10Gw6Jab0pIryMCJY4TAAxnFep7nrVI09VNrhr/dISV6st5iPBzJICnJFnwRZi1nkIoGPtwdngSGFOu3PnW3Q1tlWfGxpJRzLu56ctLPcI+f5OkLngidYpOu4P4KtFCGc+q44KKO+vfb4GojIFHbFwQHV4ymW2ptS7Y1DGI2iZ7nU976fbIqhS5Jwm2Uj1Cy3ub25c6qZUtWoNSsE+YvWLLyLmAXQrA03w1Hx4Nr9xrkTVC4iQRry7ipcnCLyd9URXKTA6JOQ4QKBgQDRtD/A0Wt3XP6W5/n4xSCv+kcJMmu4/BXeUkus00hATmat33gdsYptF/LMoAjRIfWA1j89PVLDseJ9aFH14L5U6giKmoINV+VXH6o5ZiHO3IMNjZRrpYkiQyPsvkIDUXuxMsyFDc8ejy3va2z4FZjZjHTe0w4+CBVkpxRqx9EoVQKBgQCvtrxFnmuH1aT7ie8bUWu4XmwkKaJS7dLVPXrMfTDa8g6w/AwHUJPHlNNRla7tej89JBKK4jwDyQbhvPgZUU1bpTX7JgJf4qc97bsgokepZvvgDmhik/5h6qqRzAaDqdhWRn7KVsyQGLxUuXxUezFATN5AV/jt68aCdFri9twNqQKBgB4Lw26vEsTBcEDS7//yzqIoK/FnZgPVKpT2GZ2jkCCWmyOidyVaAMlsuv8SlvDP+ssA54KwvKEJZbguMEAYeWzVM3AYfzXGODfpc4xR09o9whE5DeD2kNV15TnQcMjk7eIPszoFJn3sadp5+9z3yzSwaTZb6xh8NAR94/EAvDkZAoGBAKyM0SO4pzXz2hjdYf28ngCmUKHOdMXlH6YhwoYu5hwLmdu5F4LTYNubzUyPpgE0jAdZdhAGjBLXhTeGH0iVa1b0zSa9M5W/eKY63wjz81VqeSuUWnN7i1HSZP/ZkAgZcQWAIqBGTomukLrOj0ZS15GiKpqbCSty3jRbWKkK7BtJAoGAMRRDR3smhObNYu9vAIAj6HmuBzGmN5aM8GSyfGHa1QwTeduPpiMBG4TXdM4YM9g0IbctUSZUb/SByCjpDnN/wvJSV6ok8g/++L+6gR8tIksPR6B95fauw8OQuOpOyVKvNv5EPSz1hWxeJ8TkeyiIXZpcUNsDzSADmAIbGVbq0gg=',
        'return_url' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/paymentreturn',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAj+/n84hpdreCWrnnzSY0WYBeSS0HmMvhO7xLbHhGDq1VUEkvrWoDfjUqL5u7REm8p/IH84TMVCpHiNbB4Ny6fOWA9FhAN8aLzFAyQu0BlNfegGLuYDFKs6oAA/ztZEyinF6k1KaECo9P3j+RC4Uu1Svxv9C2GRD/Z7AsiwSjWPF+N9PN+1tkRZXDgWdPNm6iRi8vnjHjFnKyFPBfkuZVNHfK0wwYak46pjAI97AljRN/zGRbBfuFTIa97xhjils83JlQ1qp9xL29FnW2OE+BrAlDi/B+la7gQurkz8pD91mLgXOtCvgOUM7PTFBPsB4X1zYnFVt8A/N1dBgt3yvxHQIDAQAB',
        'log' => '/tmp/newalipay.log'
    ],

//支付宝原生
//支付宝原生
    'alipay_yuansheng'=>[
        'app_id'=>'2021003155623605',
        'notify_url'=>'http://api.ddyuyin.com/api/v1/appalinotify',
        'redpacket_notify_url' => 'http://api.ddyuyin.com/api/v1/api/v1/alipackets',
        'return_url'=>'',
        'ali_public_key'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsSapDTz7JFjUr5whzvgRfHbxYlV7Sh3TWCuGgU1+8m76UdyMGoPThgUTo2itM00YrfW8EtSXW4OTCa9iYgzc7aMmRUwMPio2iHuszzNReWmxgdbykXVlGPK6+Lo1X7R9hRGT06tZZf4NqUFUeAZw0JFQrTi4K0FPteATRzG4Ykv71GvUYgmCUbcf/gBnlrOhZyn3gmLKYKY4ELTvxrggDXy6frEUUVjmOEoTVxL5dvocseujvhv5oJvuekLkqM3ggxJEmn2M4HGGkqKPD1U4uf3UoGZJtL0QNgnNncg5BanMjGu/AEhhg90vDT3XJfPm1vYsb7Lv1IiiOPuBU8oJrQIDAQAB',
        'private_key'=>'MIIEpQIBAAKCAQEA1EgBQUlv+0nsId4Ov8wFcu1AOkVhUk/jU1X4wmRrnjh4OZQPgcuGzw4YR2W0WL8n2mtp8x+2cO0qup4OeCKVd8kxruhzjf5A1ID1Ady+BxEig288wwzXgiT4dn+8MLN4YQ8ZMBzHio7I/Go6aYt1KodZorcG43isPCIwnSE3A4+SBQkLSQ8L6Nom47pFHOiamPmUblC0n8AiFQO1RGWyABDP6sYBAZF2p7xTvv2j0N2SRd/QBbLyEmRf4v6qEqrDJbWwgo3+lsU7HAiBKEgJgEmMOQvfuelqp29qCxrQQpTXytSAbqzvur9NjOHD3sIxHnNUTTkSlx8Bpcsc3ZtxFQIDAQABAoIBAQCL4KZzDqDrRFqENn4hg55TjGG2A+GNC3cPgqbX8LO5HhyaVCWjsSizZuY4pZugntTz57N4sHzXDHALZ/rAzokO1VQXnLQH7HFrlU3cXEga//9t++5d2ChpaVMPQjwPGzNHQVuniE8zzcJCEP1MbshVrboyrcesO+fB+AVwhGJrxQsJ2gPXVFBMXBdEutWS1WMpzOj4q4cLqY/7foGg9NvNw8lQal8MLXFjRcFIepSucX3IkuFDeWi2gj+cyTPQggHXkAnBZ05EBSuHCoIRioL6W16zEYEEUn7xkICr5y3O6KpdTljQGBxyQNMB+/BMJWM74hXLCAIwIPt2pbmdovwBAoGBAPbWG2T8Y/Nf9zW5+wHBMi2ud1chSrK38z7SUgJCJzqyorFKiUpjGgG4od1+qGDAIZISh/i72NtVqeUXpkuhFIsWYuIKF74QdVdLkUeb4LhfS6RtP1nUcFYsTHtGs3ZzfR3ETFNbAyt1C/Fe9tzNJPIBxqkY9JorTViI6G2qFowFAoGBANwpfe4XyeB5coX6T0dzXDAG9pqZorOETJTibi7iz2p7b0+LKJu81uPneRhcPlM7MMiPDyCqJ/u0mejru0s5+F/vWQ8OCLPLiDvDPuy+UJ74yQU1deab2T0NfkKKf6twTJkTwmCvNipE9T/lvjb6uMG2fVQmJdmwYG+Wqm/SUW3RAoGBALFzzW/1TrnptOSIFt71EGjs81jNU1FWk2YHd/OtsVwujm3cwwSaaFjyblO5Ob2MgtXrwprcGRPd6u0K6n+WhxlS97W/QcBfPqyKZCBR/OUvhUbpT1D6O+SHplg9xMkUT891jtWiKY41cGePOPQV+0iMZFCu4zJujQVoL4ifbeQtAoGAPCtezk5UDvRCF1mkhxuBC2MrzG7Gp5c1ss77W/cCxtA7SJr4my+N7zVYxA6ZvfeESpvGf5/hU4o1MhIS2ulZ9yYbyeCFAlZSwjqHHP6aXAgUMEc/FKptQaFJa3gckkcbuA5NZk0cWYsFF9R7Gt2E1vQ/5lqSp57rjDO6Gtt5A7ECgYEApfjjhEBzc2VENx0DX3mSwjwF7x2CIZ6K8zuowZyaIq/BaE7SMem3FoDSoPyERxk8ul5uVYsvK9QO5Ghe07DvQqFR+mUlANTdMA5m5UuN6LcsPzqEe1rslkVUB8Vvhim/X+dK3WjCmMZpOQhoER4NFhOUQz0NUzfVq0AF9pdAGtc=',
        'log'=>'/tmp/alipay_yuansheng.log',
        'PARTNER'=> '2088041031095832',
        'vip_notify_url'=>'http://api.ddyuyin.com/api/v1/appvipalinotify',
        'sign_notify_url'=>'http://qq.api.shuoguo.xyz/api/v1/autoSignAliNotify',
    ],
//    'alipay_yuansheng' => [
//        'app_id' => '2021003128664611',
//        'notify_url' => 'http://qq.api.shuoguo.xyz/api/v1/appalinotify',
//        'return_url' => 'https://www.shuoguo.xyz/gw/#/topup',
//        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiynYlht0+A6oAk145phc0/z+IpMA7KDWORclwbvJ48CY9UZwTALCy1FRuNY0mrhUoWgxaBNaYbdMmErIVIFDe7ZN9/uKqu1Z3trgkDKRSGNLX6+oa+5Yul2SRUKLY3ihamvBkr//qRv8+n0VWmk8h9MJ6xAMZ6cZzcoyN8TQq61yK9SNHSyS8otbGwvS3QDYJJRxg4p196acYTWZkhltEKzVZYq7AEGdlDyhXeqDbcjF53ZSu+dfeWJa9O71F31D9J3PzNOUOxbzYNAL+QLVJ8jRcIfPveAeWUIKjQUzUAT+HObxzDxoJ0IYS8sQ2s2lWb4lJDgVkn9Vc5hIH8hgfwIDAQAB',
//        'private_key' => 'MIIEpAIBAAKCAQEAhYi5q3TdqCe88xDRkNU7RzF1JMuXLyWvcEhEwNv6KvWUi4ek/czuByZhkpjfzmSd032hkwiU0fIwb27b49YWZDq75Z1qqQr3hnFGifHWdv4lbxuULQv7dRX1AAZ0DQpvkpqmKKXZMOkffCa0t2rSnVcOWjIfudx1Bcl1bQRPPtT5geq7b6eqnHoh991lQ2EzW0sfvOrZ1R9NzEAtzqB0MupcNLRV0u+Oxgg8YIVEzmk4V+JqhJJq9x42qdGnHyn8tO5mTu4tVNIRcp1XuJgIoUyTKzW3nlszqzA9CYsHlmwLUN0eNbtrVeho3HN2rhllssYarprFaLtdQk1N43GzSwIDAQABAoIBADRq7WduoqwlnThU+868xV42/eMJwDkTtACBfeuu7k76w+rZvlyamz1XRaoENKaSPJoOkORk0/Zt+bFkdRDEs1l47NU/q+TblzrBMI1pz2Q6c3tf+hSMxZK6ocf2wIt180I7TspaAB4BBQj5MKtnVXHKAKpLTsTo008IO/4lWO4ynuXMaMrH9t8qOlVCfGk2OGR8FVY/Rk0RaDAMa1t28XvJK5RK8Qiq7qPM33QE6/fjnvc3AQSy9/LQKt4AcTxpa1zWk3pP0P45nx78rHi+GJ8yxkcyZSj4W2tkHyPo4CZ67nt6V2c35Sm9rp2iWjEv6ehegc5y86nkthBhGwy6SZECgYEAvPvyhIvRsRKNvTMbnYkD+hzTvcJykhv0HirBq5LozWKb5Gk+pFEjIGiQN26eI0gpeExJJOg/escDV912FKXKfY2kvXpRr4iMcAWelxxZ37YuLrsgI+rRHBGPy9U8KtozaThqZCPkEuqZ6hzMohkn+uJzTFU1SxcXOAxmGNWaPoUCgYEAtOL/puMIQDMeoaJWkjbijSTeGVUoSdRbmGF2l/erV7xRf9ctRsvQia7uyEE1chRu7e5c6B0hUOTk2/2K4wyKyS+/KHQX6TBfh5Y79OD/rHpcGAi/BPhdkUOI+O+bJWyFzcGqwyEnIP7fMwc1tXrAOngIq0/lRIkBRHc/9MWp248CgYEAj7VXWguozWo6dmFi4ozKhWteaLJwxUKUhEwnMf1pIqWVvj957yH0ADUDVeO8RUAeqOf5xyMFAqxLkolJvbHFJWyMlblqXH0NrjHXwzk/7qpuvAJ4ElgB48JHAs2ID14WMjFAVh4k1W2o1SpJQgq3KEUDQEybVhqdAXYMPV5RCLECgYBjtGvudV3q5UKRHYZSeeZOnm+9zu6yI1eJms9f3KiZZ7gUm2rdhyKfgckkoKzxAMpUY/raBpSCnmh3yj1wAU3Or37SsYssgflmJy1NQWgsjhWNOeTwvGX22B77+DawXi0yyPlzLScATPyCiArWyZ6Dm/5LT3K0YEBOmNyr8vg5iQKBgQCT45YwH6u79jl2QNYr/MF+Nix/zNkrc5UDOj89aw1k5VeVsTGX3yYgCDvzHHANmLREzexIMyjb34IwmsQEmCep2IBv9X9CdmXroNYe5StowCocqUCye5gOsHtUZW2CDoySkNtnHiDizQ89qPzNrzqRHK4spIQK29kkL6fM9lL8AQ==',
//        'log' => '/tmp/alipay_yuansheng.log',
//        'PARTNER' => '2088441299659612',
//        'vip_notify_url' => 'http://test.api.abyy.shuoguo.xyz/api/v1/appvipalinotify',
//        'redpacket_notify_url' => 'http://test.api.abyy.shuoguo.xyz/api/v1/alipackets',
//    ],

    'zeGo' => [
        'appId' => '',
        'secret' => '',
    ],

    'wechat_yuansheng1' => [
        'appid' => 'wxc4c6c84a8629a530',
        'app_id' => 'wx8d06b2c769d1ec44',//公众号id
        'miniapp_id' => '',//小程序id
        'mch_id' => '1585758811',
        'key' => 'c8837b23ff8aaa8a2dde915473ce0991',
        'notify_url' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/appwxnotify',
        'log' => '/tmp/weixin_yuansheng.log',
        'vip_notify_url' => 'https://ts.api.qqyy.jmhuyu.com/api/v1/appvipwxnotify',
    ],
// ------------ start -------------
    'wechat_yuansheng' => [
        'appid' => 'wxeadb1acd6ff08be1',
        'app_id' => 'wxae30b19810e724c5',//公众号id
        'miniapp_id' => '',//小程序id
        'mch_id' => '1603916885',
        'key' => '04316b19f32fcc1d13818ea20891f1db',
        'notify_url' => 'http://api.ddyuyin.com/api/v1/appwxnotify',
        'log' => '/tmp/weixin_yuansheng.log',
        'redpacket_notify_url' => 'http://api.ddyuyin.com/api/v1/wxpackets',
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
        'APPID' => 'wxae30b19810e724c5',
        "MCHID" => '1603916885',
        'APIKEY' => '04316b19f32fcc1d13818ea20891f1db',
        'APPSECRET' => '56ebbfe59f5fd2bb512469e58486c6a0',
        'PLACE_ORDER' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
        'notify_url' => 'http://api.ddyuyin.com/api/v1/appwxnotify',
        'payment' => 'http://api.ddyuyin.com/api/v1/payment',
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

        "Appkey" => '163e50c05b5890d12b0ab167ea8d422e',
        "Appsecret" => '9cb2e363dce3',

    ],
//消息url

    'socket_url' => 'http://py.abyuyin.com/iapi/broadcast',
    'socket_url_base' => 'http://py.abyuyin.com/',

    'getui'=> [
        'appid' => 'yektrexMpx97bw36KYPsiA',
        'appkey' => 'qb2Xe0udav976L7CGidrH4',
        'mastersecret' => 'dBpseHNw8k57z0wP2k8PxA',
        'host' => 'http://sdk.open.api.igexin.com/apiex.htm',
        'loginurl'=>'https://openapi-gy.getui.com/v1/gy/ct_login/gy_get_pn',
    ],

    'yunxin_prefix' => 'test_',
    'user_online_single_url' => 'http://182.92.186.104:9081/api/query/online',
    'user_online_all_url' => 'http://182.92.186.104:9081/api/query/device',
    'fq_assistant' => '1000004',
    'service_customer' => '1000004',
    'gift_box_id' => '376',

    'scale' => 1000,    //豆兑换钻石比例 1:1000
    'khd_scale' => 10000,//钱兑换钻石 1:10000
    'bean_coin_scale' => 100,//豆兑换金币 1:100
    'self_scale' => 0.5,//个人比例
    'coin_scale' => 10,//钱兑换豆 1:10

    'duobao' => 'https://ts.api.qqyy.jmhuyu.com/build/web-mobile/index.html?mtoken=',

    'smsother' => [
        'appkey' => '8X603i',
        'appsecret' => 'L3st32',
        'appcode' => '1000',
        'host' => 'http://39.97.4.102:9090/sms/batch/v1',
    ],

    'exchange_gift' => [
        'public_screen' => [397],
        'float_screen' => [396, 372, 231]
    ],
    'shumei' => [
        'AccessKey' => 'F1J4MA4kLYJae7HMR4y8',
        'AppId' => 'default',
        'audioStreamSwitch' => 0
    ],
    'heartInterval' => 20,//用户心跳间隔时间
    'threeLoot' => 'https://ts.api.qqyy.jmhuyu.com/sanrenduobao?mtoken=',
    'baoxiang' => 'http://www.ddyuyin.com/baoxiang?mtoken=',
    'zhuanpan' => 'http://www.ddyuyin.com/zhuanpan?mtoken=',
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
    'EncryptKey' => "na2wtspma4BtZ4XLb1ThQA==",   //encryptKey  打乱后的假值：下发假值，逆向为真值用
    'EncryptKeySecond' => "aa4BtZ4tspm2wnXLb1ThQA==",   //encryptKey  error
    'apiSignAuthKey' => "sichuanrongqisign20200223",   //api auth sign key
    'apiSignEnable' => "enable",   //api sign auth "enable"// "enable": "off"
    'apiAuthTimestamp' => 'off', // enbale:"enable"  close:"off"
    'apiSignSalt' => "sichuanrongqisign20200223",
    'ossUrl' => 'http://image2.fqparty.com',
    'appDev' => Env::get('APPDEV', "dev"),  // 测试:"dev"  正式:"online"
    'firstPayUrlIndex' => 'https://ts.api.qqyy.jmhuyu.com/shouchong?mtoken=',
    'firstPayUrlRoom' => 'https://ts.api.qqyy.jmhuyu.com/firstpay?mtoken=',
    'firstChargeUrlIndex' => "http://test.activity.muayuyin.com/activity/shouchong2/index.html",
    'baseUrl2' => [
        'online_url' => [
            'api_url' => ['http://api.ddyuyin.com'],
            'py_url'  => ['http://py.ddyuyin.com/'],
            'ws_url'  => ['ws://py.ddyuyin.com/ws'],
        ],
        'test_url' =>  [
            'api_url' => ['http://api.ddyuyin.com'],
            'py_url'  => ['http://py.ddyuyin.com'],
            'ws_url'  => ['ws://py.ddyuyin.com/ws'],
        ],
    ],


    'ampq_recall' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_member_recall',
        'exchange' => 'ex_member_recall',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],

//    ampq_recall_user_push
    'ampq_recall_user_push' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_member_recall_user_push',
        'exchange' => 'ex_member_recall_user_push',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],

    // mq
    'ampq_im_message' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_im_message',
        'exchange' => 'ex_im_message',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],

    'ampq_login_detail_message' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_login_detail_message',
        'exchange' => 'ex_login_detail_message',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],


    'ampq_elastic_room' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_message_bus_elastic_room',
        'exchange' => 'ex_message_bus',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],
    'ampq_elastic_user' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_message_bus_elastic_user',
        'exchange' => 'ex_message_bus',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],

    'ampq_message_bus' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'routing_key' => "ex_message_bus",
        'queue_name' => '',
        'exchange' => 'ex_message_bus',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],

    'ampq_user_register_referee' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_user_register_referee',
        'exchange' => 'ex_user_register_referee',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],


    // es
    'es_host' => env('ES_HOST', '172.31.48.10:9200'),

    'open_first_charge' => false,  // 是否开启首充 true开启，false关闭

    // 特别关心
    'ampq_user_special_care' => [
        'host' => Env::get('rabbitmq.host', '172.31.48.10'),
        'vhost' => '/',
        'queue_name' => 'q_user_special_care',
        'exchange' => 'ex_user_special_care',
        'port' => Env::get('rabbitmq.port', 5672),
        'user' => 'fanqie',
        'pass' => 'fanqie123',
    ],
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
);

