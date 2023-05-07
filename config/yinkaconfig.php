<?php

/**
 * Created by PhpStorm.
 * User: sh
 * Date: 2019/7/24
 * Time: 9:42
 */
return array(
    //短信验证码
    'ALISMS' => [
        'accessKeyId' => 'LTAI4G1bUCQXqwjG8qo91u3b',
        'accessSecret' => 'l1TZsfl9jls6OtjjsA4MFms7bRLPBV',
        'ali_sms_regionId' => 'cn-hangzhou',
        'ali_sms_signName' => '讯视云创',
        'ali_sms_templateCode' => 'SMS_187954005'
    ],
    'VERTIFYCODE'=>true,
    'THIRDLOGIN'=>[
        3 => [
            'app_id'     => 'wx9cdc0137454e2af1',
            'app_secret' => '6d1aa06de94ba2af10658a3630644ea5',
            'scope'      => 'snsapi_base'
        ],
    ],

    //支付宝支付配置
    'ALIPAY' => [
        'app_id'=>'2018053160274419',
        'subject'=>'MUA语音',
        'partner'=>'2088131391400150',
        'service'=>'mobile.securitypay.pay',
        'input_charset'=>'utf-8',
        'seller_id'=>'',
        'transport'=>'http',
        'notify_url'=>'http://newmtestapi.57xun.com/api/v1/paymentnotify',
        'http_verify_url'=>'http://notify.alipay.com/trade/notify_query.do?',
        'https_verify_url'=>'https://mapi.alipay.com/gateway.do?service=notify_verify&',
        'private_key'=>'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCP7+fziGl2t4JauefNJjRZgF5JLQeYy+E7vEtseEYOrVVQSS+tagN+NSovm7tESbyn8gfzhMxUKkeI1sHg3Lp85YD0WEA3xovMUDJC7QGU196AYu5gMUqzqgAD/O1kTKKcXqTUpoQKj0/eP5ELhS7VK/G/0LYZEP9nsCyLBKNY8X430837W2RFlcOBZ082bqJGLy+eMeMWcrIU8F+S5lU0d8rTDBhqTjqmMAj3sCWNE3/MZFsF+4VMhr3vGGOKWzzcmVDWqn3Evb0WdbY4T4GsCUOL8H6VruBC6uTPykP3WYuBc60K+A5Qzs9MUE+wHhfXNicVW3wD83V0GC3fK/EdAgMBAAECggEACcROqfDEr0COgNeCiiIghT43p6F9lXmoI+SH/ak/n7lVQ6hjqtG5wPOclDRuBZk3SvIaZgTJ1KA10Gw6Jab0pIryMCJY4TAAxnFep7nrVI09VNrhr/dISV6st5iPBzJICnJFnwRZi1nkIoGPtwdngSGFOu3PnW3Q1tlWfGxpJRzLu56ctLPcI+f5OkLngidYpOu4P4KtFCGc+q44KKO+vfb4GojIFHbFwQHV4ymW2ptS7Y1DGI2iZ7nU976fbIqhS5Jwm2Uj1Cy3ub25c6qZUtWoNSsE+YvWLLyLmAXQrA03w1Hx4Nr9xrkTVC4iQRry7ipcnCLyd9URXKTA6JOQ4QKBgQDRtD/A0Wt3XP6W5/n4xSCv+kcJMmu4/BXeUkus00hATmat33gdsYptF/LMoAjRIfWA1j89PVLDseJ9aFH14L5U6giKmoINV+VXH6o5ZiHO3IMNjZRrpYkiQyPsvkIDUXuxMsyFDc8ejy3va2z4FZjZjHTe0w4+CBVkpxRqx9EoVQKBgQCvtrxFnmuH1aT7ie8bUWu4XmwkKaJS7dLVPXrMfTDa8g6w/AwHUJPHlNNRla7tej89JBKK4jwDyQbhvPgZUU1bpTX7JgJf4qc97bsgokepZvvgDmhik/5h6qqRzAaDqdhWRn7KVsyQGLxUuXxUezFATN5AV/jt68aCdFri9twNqQKBgB4Lw26vEsTBcEDS7//yzqIoK/FnZgPVKpT2GZ2jkCCWmyOidyVaAMlsuv8SlvDP+ssA54KwvKEJZbguMEAYeWzVM3AYfzXGODfpc4xR09o9whE5DeD2kNV15TnQcMjk7eIPszoFJn3sadp5+9z3yzSwaTZb6xh8NAR94/EAvDkZAoGBAKyM0SO4pzXz2hjdYf28ngCmUKHOdMXlH6YhwoYu5hwLmdu5F4LTYNubzUyPpgE0jAdZdhAGjBLXhTeGH0iVa1b0zSa9M5W/eKY63wjz81VqeSuUWnN7i1HSZP/ZkAgZcQWAIqBGTomukLrOj0ZS15GiKpqbCSty3jRbWKkK7BtJAoGAMRRDR3smhObNYu9vAIAj6HmuBzGmN5aM8GSyfGHa1QwTeduPpiMBG4TXdM4YM9g0IbctUSZUb/SByCjpDnN/wvJSV6ok8g/++L+6gR8tIksPR6B95fauw8OQuOpOyVKvNv5EPSz1hWxeJ8TkeyiIXZpcUNsDzSADmAIbGVbq0gg=',
        'return_url'=>'http://newmtestapi.57xun.com/api/v1/paymentreturn',
        'ali_public_key'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAj+/n84hpdreCWrnnzSY0WYBeSS0HmMvhO7xLbHhGDq1VUEkvrWoDfjUqL5u7REm8p/IH84TMVCpHiNbB4Ny6fOWA9FhAN8aLzFAyQu0BlNfegGLuYDFKs6oAA/ztZEyinF6k1KaECo9P3j+RC4Uu1Svxv9C2GRD/Z7AsiwSjWPF+N9PN+1tkRZXDgWdPNm6iRi8vnjHjFnKyFPBfkuZVNHfK0wwYak46pjAI97AljRN/zGRbBfuFTIa97xhjils83JlQ1qp9xL29FnW2OE+BrAlDi/B+la7gQurkz8pD91mLgXOtCvgOUM7PTFBPsB4X1zYnFVt8A/N1dBgt3yvxHQIDAQAB',
        'log'=>'/tmp/newalipay.log'
    ],

//    'alipay_yuansheng'=>[
//        'app_id'=>'2021003155623605',
//        'notify_url'=>'http://qq.api.shuoguo.xyz/api/v1/appalinotify',
//        'redpacket_notify_url' => 'http://qq.api.shuoguo.xyz/api/v1/alipackets',
//        'return_url'=>'',
//        'ali_public_key'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsSapDTz7JFjUr5whzvgRfHbxYlV7Sh3TWCuGgU1+8m76UdyMGoPThgUTo2itM00YrfW8EtSXW4OTCa9iYgzc7aMmRUwMPio2iHuszzNReWmxgdbykXVlGPK6+Lo1X7R9hRGT06tZZf4NqUFUeAZw0JFQrTi4K0FPteATRzG4Ykv71GvUYgmCUbcf/gBnlrOhZyn3gmLKYKY4ELTvxrggDXy6frEUUVjmOEoTVxL5dvocseujvhv5oJvuekLkqM3ggxJEmn2M4HGGkqKPD1U4uf3UoGZJtL0QNgnNncg5BanMjGu/AEhhg90vDT3XJfPm1vYsb7Lv1IiiOPuBU8oJrQIDAQAB',
//        'private_key'=>'MIIEwAIBADANBgkqhkiG9w0BAQEFAASCBKowggSmAgEAAoIBAQC7ScR0EjzJj70vyzyliKT72jx2ImLb1DliwKPA/ZZKKB4AQYZiKJrby5QXqZlbAteCZjGr6oMhKUGbXsn3IU0TquJOOwoxw8J5h2/hvgP9EaahkQQUEt1/18Bgrq5WHbyajW4eMmhiRmwvwtWnRPlaPRarhH8fvbmA1WuS5A6fh/MWhoh2d3qCOfleQUDelDNtX9RM8IGKduTOKecLyMfU0hBj95aEzpdKVWDY3siMf9fuC3MBKbURY/ygQjueTuZx29bSJI5HdWYHX+RCbcoLil0xMV8bWnCVcn+3e7Koqj4OfqeLU+PN82xgb52gSsSkQHf+DFN1SLu0tlGfUYJXAgMBAAECggEBALLNsmi+IJquSAghGD1RP3HS4HZITelhq4fEMpJFh/40XrAP3qRH79B3g2Mz390r0WQE/NcMhTRblu8mh9sQR+3G71eQtiLcqgYsb3wtVNF+0H7fW+1uNVmgOWIUYwyER1OmQsNBjLaGVTMj56ZSC11DhSkqzdMX5spA9vq+D9fs6I93VtbIGK+KE+lgAj9aucDeBLx7Yzn9wM/X9uxaWIcODA43ZetYbNZgovUv6ilJcuMtX4IYaiPjkE722sps2PN/dxfJWh+13eqGutBdjYNwnZyLgXUF3u/Lsn6anzDea9iQxQ3bUhN4Wq9hC9hd6QZ866J8JRNcpu704N3l7MECgYEA4/gPtNX0fTYn5ePp4zLqQ135u9xDlIVQBDhr3hE1ksA8cbMr3fMKGhQYblgtnvL+2Y1GNrHFuU1TiO8J5Sr/KUZJi1WLw5mpTiLzzOZiCfV1TNupurrXOSxOXeDeZsevm9r5/BSxSjkFZ7XzS5MWf4KPRe5SpNbilyTpSxv5JSMCgYEA0lEqw3cC6OgHdOh4P0PImnulT79leOV2gPNi8iWMJmC5zyZbPE5YeLMfWgtNTHRnP7nEzJBdWqwUsKEvQ34+3jIdwcjcijl3ZEFc+tITz6muJhzdkqJGUWjmt4Od7WYchmpkPeGJoBm3EW7EOGqVHpZsnM3eYH0SAt2GUv9Owz0CgYEAzuSUrJP43aJGt3cPD2ln/lfNjFcs5h/PLOVf0BxiOJtKwA3R5A7svho6yfow/S6faCW08XKJddDd3UrD+j35cSYfHNs4iv5sD0Pda7oyg8NG/8fj1Fo5dePmA4FPlovnrlUfx02oUSpK44LYGWCWbIa7LMZMLtnurymKqOXsSW0CgYEAhBxGP3gvlLvDi1VHy325sgh/RAPXKsUA1mmMOs0JJ0ZVEWFnqA8Sleb2h6pXyPHJtYbsrw70BTPY30awmLXhqdNTS9nvZshITeqdDFUP6r8MTJaPPD/A6fx6CMadWnVs76Y/B3v35mCg1Ut96G5S817MJQdQa83ElZYvfU0wXYUCgYEAoKM2ItmcSiZC3atZEsBaXUGm4U3lLU7LmsZRNdZMRSGJOjtPrF6ZLLotdBWTpGfvbTE0xFDXNL3zA8c1oF1Vkf1zn970ekF1KYAyKs1oaQtnO1hRVS+4PwAhDNiDxavDu9Wp44f/17UIE6Rp09rZoSIMYrVyDnaNNilNZ6YAf9A=',
//        'log'=>'/tmp/alipay_yuansheng.log',
//        'PARTNER'=>2088041031095832,
//        'vip_notify_url'=>'http://qq.api.shuoguo.xyz/api/v1/appvipalinotify',
//        'sign_notify_url'=>'http://qq.api.shuoguo.xyz/api/v1/autoSignAliNotify',
//    ],

    'alipay_yuansheng' => [
        'app_id' => '2021003128664611',
        'notify_url' => 'http://qq.api.shuoguo.xyz/api/v1/appalinotify',
        'return_url' => 'https://www.shuoguo.xyz/gw/#/topup',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiynYlht0+A6oAk145phc0/z+IpMA7KDWORclwbvJ48CY9UZwTALCy1FRuNY0mrhUoWgxaBNaYbdMmErIVIFDe7ZN9/uKqu1Z3trgkDKRSGNLX6+oa+5Yul2SRUKLY3ihamvBkr//qRv8+n0VWmk8h9MJ6xAMZ6cZzcoyN8TQq61yK9SNHSyS8otbGwvS3QDYJJRxg4p196acYTWZkhltEKzVZYq7AEGdlDyhXeqDbcjF53ZSu+dfeWJa9O71F31D9J3PzNOUOxbzYNAL+QLVJ8jRcIfPveAeWUIKjQUzUAT+HObxzDxoJ0IYS8sQ2s2lWb4lJDgVkn9Vc5hIH8hgfwIDAQAB',
        'private_key' => 'MIIEpAIBAAKCAQEAhYi5q3TdqCe88xDRkNU7RzF1JMuXLyWvcEhEwNv6KvWUi4ek/czuByZhkpjfzmSd032hkwiU0fIwb27b49YWZDq75Z1qqQr3hnFGifHWdv4lbxuULQv7dRX1AAZ0DQpvkpqmKKXZMOkffCa0t2rSnVcOWjIfudx1Bcl1bQRPPtT5geq7b6eqnHoh991lQ2EzW0sfvOrZ1R9NzEAtzqB0MupcNLRV0u+Oxgg8YIVEzmk4V+JqhJJq9x42qdGnHyn8tO5mTu4tVNIRcp1XuJgIoUyTKzW3nlszqzA9CYsHlmwLUN0eNbtrVeho3HN2rhllssYarprFaLtdQk1N43GzSwIDAQABAoIBADRq7WduoqwlnThU+868xV42/eMJwDkTtACBfeuu7k76w+rZvlyamz1XRaoENKaSPJoOkORk0/Zt+bFkdRDEs1l47NU/q+TblzrBMI1pz2Q6c3tf+hSMxZK6ocf2wIt180I7TspaAB4BBQj5MKtnVXHKAKpLTsTo008IO/4lWO4ynuXMaMrH9t8qOlVCfGk2OGR8FVY/Rk0RaDAMa1t28XvJK5RK8Qiq7qPM33QE6/fjnvc3AQSy9/LQKt4AcTxpa1zWk3pP0P45nx78rHi+GJ8yxkcyZSj4W2tkHyPo4CZ67nt6V2c35Sm9rp2iWjEv6ehegc5y86nkthBhGwy6SZECgYEAvPvyhIvRsRKNvTMbnYkD+hzTvcJykhv0HirBq5LozWKb5Gk+pFEjIGiQN26eI0gpeExJJOg/escDV912FKXKfY2kvXpRr4iMcAWelxxZ37YuLrsgI+rRHBGPy9U8KtozaThqZCPkEuqZ6hzMohkn+uJzTFU1SxcXOAxmGNWaPoUCgYEAtOL/puMIQDMeoaJWkjbijSTeGVUoSdRbmGF2l/erV7xRf9ctRsvQia7uyEE1chRu7e5c6B0hUOTk2/2K4wyKyS+/KHQX6TBfh5Y79OD/rHpcGAi/BPhdkUOI+O+bJWyFzcGqwyEnIP7fMwc1tXrAOngIq0/lRIkBRHc/9MWp248CgYEAj7VXWguozWo6dmFi4ozKhWteaLJwxUKUhEwnMf1pIqWVvj957yH0ADUDVeO8RUAeqOf5xyMFAqxLkolJvbHFJWyMlblqXH0NrjHXwzk/7qpuvAJ4ElgB48JHAs2ID14WMjFAVh4k1W2o1SpJQgq3KEUDQEybVhqdAXYMPV5RCLECgYBjtGvudV3q5UKRHYZSeeZOnm+9zu6yI1eJms9f3KiZZ7gUm2rdhyKfgckkoKzxAMpUY/raBpSCnmh3yj1wAU3Or37SsYssgflmJy1NQWgsjhWNOeTwvGX22B77+DawXi0yyPlzLScATPyCiArWyZ6Dm/5LT3K0YEBOmNyr8vg5iQKBgQCT45YwH6u79jl2QNYr/MF+Nix/zNkrc5UDOj89aw1k5VeVsTGX3yYgCDvzHHANmLREzexIMyjb34IwmsQEmCep2IBv9X9CdmXroNYe5StowCocqUCye5gOsHtUZW2CDoySkNtnHiDizQ89qPzNrzqRHK4spIQK29kkL6fM9lL8AQ==',
        'log' => '/tmp/alipay_yuansheng.log',
        'PARTNER' => '2088441299659612',
        'vip_notify_url' => 'http://test.api.abyy.shuoguo.xyz/api/v1/appvipalinotify',
        'redpacket_notify_url' => 'http://test.api.abyy.shuoguo.xyz/api/v1/alipackets',
    ],

    //微信支付原生
    'wechat_yuansheng'=>[
        'appid'=>'wx9cdc0137454e2af1',
        'app_id'=>'wx8d06b2c769d1ec44',//公众号id
        'miniapp_id'=>'',//小程序id
        //'mch_id'=>'1606361328',
        'mch_id'=>'1585758811',
        'key'=>'c8837b23ff8aaa8a2dde915473ce0991',
        'notify_url'=>'http://test.php.fqparty.com/api/v1/appwxnotify',
        'log'=>'/tmp/weixin_yuansheng.log',
        'vip_notify_url'=>'http://fqparty.com/api/v1/appvipwxnotify',
        'redpacket_notify_url'=>'http://test.php.fqparty.com/api/v1/wxpackets',
    ],

    'getui'=> [
        'appid' => 'yektrexMpx97bw36KYPsiA',
        'appkey' => 'qb2Xe0udav976L7CGidrH4',
        'mastersecret' => 'dBpseHNw8k57z0wP2k8PxA',
        'host' => 'http://sdk.open.api.igexin.com/apiex.htm',
        'loginurl'=>'https://openapi-gy.getui.com/v1/gy/ct_login/gy_get_pn',
    ],

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

    'shumei' => [
        'AccessKey' => 'F1J4MA4kLYJae7HMR4y8',
        'AppId' => 'qingqingvoice',
    ],
);
