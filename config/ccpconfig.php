<?php
use think\facade\Env;
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
        'ali_sms_signName' => '音恋语音',
        'ali_sms_templateCode' => 'SMS_187954005'
    ],
    'VERTIFYCODE'=>false,
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


    //支付宝原生
    //'alipay_yuansheng'=>[
    //    'app_id'=>'2021002127633238',
    //    'notify_url'=>'http://fqparty.com/api/v1/appalinotifymua',
    //    'return_url'=>'',
    //    'app_secret_cert'=>'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQCyn4rrWOYLSZogapUVfjQfZMueI23XX4VMuQRjn+Qs5ls+KaIdpktXPZP+/zqJQYZmp7vfHoG8msFuoAn3NQxjUx1MQ0bFIb8pqxJuQAfDCSauD/ZwQw2TOuoipeOQ7vwK78byEkMvP7I/MONylby/CKfxSEDaJofcx+zCi6ID8C7yzvhueFttr+8pIKODnNGtNkeQMWNl/xvoZ5CpY0K5TfbNmTh/+Z14xc/5nEMQn/qCJD7kV+zlfVFbKMkKTilDVvUviXV3bjaZaAojVd2qvKPe1XVUkm9CZrouXHo3JzTT244iRwbAKKS2y/3ZXwx/9WyrW08GVLOi7E5zrHzNAgMBAAECggEAfhPiiC+dYEY5Rbw1MpB71dTda+dx9lzzx62oDXGkKpUFviDAztC7yP4r4gbgmFTpureWAeyUgj1xXtYTcqu87LaTrWqnHymstoqLXcpQMDfbV2zo82BAyrUA4ifZMkmdGVMZR2/ggA8jjzrAr/ZE3UoM0F/BdrlPmGR9FuxcFQ5X+OY5Qf81Ovz2ZepR0IgfOyyA/5hlTnUNAF5QJ5n0KEXYcs5bMlmWP2aVKqksH6TTesNFne56/ZzLp2YCaxdUlZRj7Rwfr+oFgZ5jduJRTyof/zOf4zYCVUeJGLfl0PWWuvgcpgAL/XUZPU+w/2OIB8kdSWbaGIkOMkEf6NlpwQKBgQD20lVjDnh2nEPvxD2l67GuVdNVg5QDvIgZYnFdUyJGjNrC+XZJXjhe/jMDJuUUcZuIjAPnHba7VyR5LWbBIbuS8eBHvhQySDmyWGM4SU3608OowXycbNn4dwRbFadZ/qpzSIAWUoCwAtrigjdYm5kA+3iop1q9VYH1Xtm7TvL/1QKBgQC5Q/srwnBf7id854XDaddfDEcyORoQlb01V2D5XIHCJu1ITS/F/KNpWiZOD7jy3qINZYlHpIQ2Jh4WK+lQQZjGtDtxQZkkQlrh+5lDNOzjqWKZjQG45A645gx08azMrAWpiLURh0BElDvvSNd2ozZQhXz9//j6n0MuvxFg5Kz9GQKBgQCGfiA3BMiRYR8HxHgPNDoyk+O/Yh5CVvYWVvUM9GLl7JS7z3EsE2JGKN+lJQmItUsaAamSwzcyKA7g1ON786ShMpmSnjmlGIQP8WfHYPJT7hZcm2oKVqoDYN8Hvunc+Q7qGKRrBXPH9RZOfMQpzUd3KJAb3m1sY+6XxKKCFUgGLQKBgQCq1ZT2dQVKcEDZXGRsHV0LuDauiRkP0gP2++vgBP1iGqMS2JoE50GIFCjeMoFI8yJbWBWOipWfmOaFa1hpORO3ptppRSQB224Sk/5vio4mIDtbfDrqUuGAfiFedLvyv205N2ZAE4eftVDPBUwpiba76VuonDDqaZF5uZY80qDxgQKBgQCxvALSALEuwsL81GqDHZSCq8ywzaKvdeUtewVFfuftOC5C4KnVFE+e3+Kzsira3YNR9vRhOEtHbiVI8lwdhncRw7XnkRCp52TmoX2n0amRMFPvp6GZ0CfHVXN32aZJGeBnvCRb9UI9GFx7QM7pAEjg/xtbtyMbhjUKg8pYWPyNJQ==',
    //	'app_public_cert_path' => '/www/wwwroot/mua/config/StAliCert/appCertPublicKey_2021002127633238.crt',
    //	'alipay_public_cert_path' => '',
    //	'alipay_root_cert_path' => '',
    //    'log'=>'/tmp/alipay_yuansheng.log',
    //    'PARTNER'=>2088041669678805,
    //    'vip_notify_url'=>'http://fqparty.com/api/v1/appvipalinotifymua',
    //    'redpacket_notify_url'=>'http://fqparty.com/api/v1/alipacketsMua',
    //],

    'alipay_yuansheng'=>[
        'app_id'=>'2021001146649415',
        'notify_url'=>'http://test.php.fqparty.com/api/v1/appalinotify',
        'redpacket_notify_url' => 'http://test.php.fqparty.com/api/v1/alipackets',
        'return_url'=>'',
        'ali_public_key'=>'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtGFfhjmxZhJkwVtDuqFc8c7x16tDvudtHKgQfJkJE7ZLk2+yyL/U6uNTEN7Nn6X2bsH+xiqLT8AC69qkudLNOCB28dskBwPAHIO5VZ0wQYUNVH2RAZFMfkVTnlUBfssSlRjT9XjBPEKAFFVRDpg4uy1mNp8y52UoBn+jL24L1x9DUT3HVKfRBtTxcbgz55QhIbQ9xd5DijuNfh1oRIhqvdM/zGC0fRyFpWk/9MbgwwFQOi3atG3jS8po7i9Vps8i5PqNGAyu9UT7HffNBPJ3KMK2RFP0rJQTLb0mvwRNgQoFI7dNZNueZfMaVd2v+iuWZ560YxFLqzt+EV9TxSTtxwIDAQAB',
        'private_key'=>'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC0YV+GObFmEmTBW0O6oVzxzvHXq0O+520cqBB8mQkTtkuTb7LIv9Tq41MQ3s2fpfZuwf7GKotPwALr2qS50s04IHbx2yQHA8Acg7lVnTBBhQ1UfZEBkUx+RVOeVQF+yxKVGNP1eME8QoAUVVEOmDi7LWY2nzLnZSgGf6MvbgvXH0NRPcdUp9EG1PFxuDPnlCEhtD3F3kOKO41+HWhEiGq90z/MYLR9HIWlaT/0xuDDAVA6Ldq0beNLymjuL1WmzyLk+o0YDK71RPsd980E8ncowrZEU/SslBMtvSa/BE2BCgUjt01k255l8xpV3a/6K5ZnnrRjEUurO34RX1PFJO3HAgMBAAECggEABqQzLTEAnB5/QTNICh4Y2vpgoy02IFhLBywU91F3KRekLriFH6kYgNF/5HYfogotSaSw6cD1QilyshZpzEG7tp5TYtJLwEpeD262KzXPagopFHxPE3yzEU8iJglBNdbqRz0TyMz6aGqWZqlBg8UNCa9BPS+dYzGxSJP4gS2PGAuJuMAWdHuQ5rDnZt3o4BHukipOulFhThEuYOX3NS5/MBaiLVzMqKylVISvsXAOtGbv9l2Z+YJwWvyVlpf1YQ5RmAU7Yt/53dVXvlZsa+C1VnHneZgO7fsiDhYc+TDcUQSfn0rWJB40j9ELRdgUgS1eJgc6gC9VsO2NHPG4iGVbaQKBgQDunPYoP5tz79Qwfpwf/UBZeiSgm2vOc0YBMB4rsGcdU6krLdyrpxP6H2RwB+/NqCo5OL/KvqDwc6lSmNrcW4Q0ROCT+xslhVFC85+M+Hfh0UIihIIohLV4q9jf1qGkoyEtGjkaLUMnVXzJfNTH2MFMGcoa26d9qouVZa4j7WYliwKBgQDBhiZkkKSryGjR8t1MdutsZ7TdEqvJfdEceauf+KFsHoor9bnSJf6SZu6d1im0hzK29c5bl4wI3cIuFDzRdH0LcknHBrtZ7aYa3WkJzcPDlOdjh9o6sw48Kxcx3olUUwqei00IkrEcxxacgNW7wbvXJvX2a/PzG2WItgAswPJ4NQKBgQDWte7asMHVRU2OhZ8/OceZvEsRKkmL8DZiA0Zi0c03mnxzGkWjQCi9vVnHZZznVhcIfoQ2j+qJ88m9RUZLWx5PWlsrTZ1T2e8Ra996HmrhjEcSgGIOy7vv5dK1OJEjcJb92sbfQzNWRZqQQ//EBMuLCvnNyTGh5sDLoj9cnNZ5mQKBgGP2I0a24BhLgLlRtbWVh62LNAUta8a8UpNe1aPgDaGHdN+YIc/HCGQe/wpQYWJ6o1uWRG1TLmY2BeM/WNroTN7ovessMwIUm0QtMeB6hLA17f+fYL4JV0UFDQoZZN5FtqqGUdgnzYyL2cYlVkGRpLFR0qLiyDO+5wdi21xCnfwxAoGAdjF/t7MwBwvH0tEPhb5PTHKJRpjEbAJjPyCLc9/sihZSeOv/JZndML/WHn7NFloknxJ+VzkLbQlbAy7FAMBhJPBFZ2IAIg3Qn9fpqvauudFu2rntUK8WBgWAjHrtgAJ2LV5eXVajc+P7LUFDxvKDUhFXLNrDkzGRywPZ+yANRK0=',
        'log'=>'/tmp/alipay_yuansheng.log',
        'PARTNER'=>2088731855039665,
        'vip_notify_url'=>'https://recodetest1.fqparty.com/api/v1/appvipalinotify',
        'sign_notify_url'=>'http://test.php.fqparty.com/api/v1/autoSignAliNotify',
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
        'appid' => 'iWeaFFHKKI69ZWHjYJeiF7',
        'appkey' => 'K3lReAOClQ6xvMdwhk1H05',
        'mastersecret' => 'dXJAOAAOcd7JspVBrXzBY',
        'host' => 'http://sdk.open.api.igexin.com/apiex.htm',
        'loginurl'=>'https://openapi-gy.getui.com/v1/gy/ct_login/gy_get_pn',
    ],
);
