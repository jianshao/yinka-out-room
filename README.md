ThinkPHP 6.0
===============

> 运行环境要求PHP7.1+。

## 主要新特性

* 采用`PHP7`强类型（严格模式）
* 支持更多的`PSR`规范
* 原生多应用支持
* 更强大和易用的查询
* 全新的事件系统
* 模型事件和数据库事件统一纳入事件系统
* 模板引擎分离出核心
* 内部功能中间件化
* SESSION/Cookie机制改进
* 对Swoole以及协程支持改进
* 对IDE更加友好
* 统一和精简大量用法

## 安装

~~~
composer create-project topthink/think tp 6.0.*-dev
~~~

如果需要更新框架使用
~~~
composer update topthink/framework
~~~

## 文档

[完全开发手册](https://www.kancloud.cn/manual/thinkphp6_0/content)

## 参与开发

请参阅 [ThinkPHP 核心框架包](https://github.com/top-think/framework)。

## 版权信息

ThinkPHP遵循Apache2开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2006-2019 by ThinkPHP (http://thinkphp.cn)

All rights reserved。

ThinkPHP® 商标和著作权所有者为上海顶想信息科技有限公司。

更多细节参阅 [LICENSE.txt](LICENSE.txt)



```
{
	"code": 200,
	"desc": "success",
	"data": {
		"areas": [{
			"displayName": "资料",
			"data": [
				{
					"displayName": "终极礼物",
					"gifts": [{
						id:1,
						count:10
					}]
				}
			]
		}, {
			"displayName": "动态",
			"data": []
		}]
	}
}
```


areas demo:
```

data:{
	privilege:[
		{
            "id": 1,
            "type": 1,
            "picture": "http://image2.fqparty.com/privilege/20200805/4efe8bbca9154d4628f2c719c80f0baa.png",
            "title": "会员标识",
            "previewPicture": "http://image2.fqparty.com/banner/20200806/a3cd9068bf9e2323de2e5393aadf6a02.png",
            "content": "在会员有效期内，专属会员标识将出现在您的个人资料页、进房信息、公屏聊天中，突 显尊贵身份。",
        }
	],

"areas": [
            {
                "type": "vip",
                "displayName": "头像框",
                "ids": []
                },
            {
            	"type": "svip",
            	"displayName": "头像框",
            	"ids": []
            }
       ]
}

```







### msgId:

2031 热度值变更
2030 送礼物





### api加密和 sign签名教研功能 说明文档

1. 需要同时在 role 中引入 [\app\middleware\ResponseLog::class, \app\middleware\AuthSign::class]  两个中间件

1. 配置并开启指定的配置项参数：
```   
   'EncryptDriver' => "enable",   // enbale:"enable"  close:"off" 是否开启加密模式 

   'EncryptKey' => "aa4BtZ4tspm2wnXLb1ThQA==",   //encryptKey  error  // 加密模式的加密 salt

   'apiSignEnable' => "enable",   //api sign auth "enable"// "enable": "off"  //是否开启api的签名验证

   'apiSignSalt' => "6e9633564597df2f00c51b8ed59ddf2e",   // api 加密的 salt
```

1. 声明是app请求 配置请求headers的id参数: id=123123bb 

1. 完整的请求demo url     
```
http://www.fanqieapi.com/api/v2/test/test?data=34IiljN8wG3hfF0ZUZdexwBk6mYumTX1CUnIvXD99u4wdExTgD4YxGeXmJTZQyIlUfSz%2Fmtr4vUGZ5lhPnHnqz%2BDszh52ldvkpaOpVchrWJhi7yGGfX1oaCYKcPHfHXuUthfihxKAl13eSUv1fas3Q%3D%3D
```


1. 生成签名的验证逻辑demo （注意生成的加密param参数 需要urlencode ）
```
//        -----------------
        $Aes=new Aes();
        $key=config('config.EncryptKey');
        $re=$Aes->aesEncrypt("num=1&giftId=365&skip=1&usePack=0&touId=1451150&mic=999&roomId=124206&sign=88d00e59dcea45d6cbd3703013fdd3a1",$key);
        $re=urlencode($re);
        var_dump($re);die;// 34IiljN8wG3hfF0ZUZdexwBk6mYumTX1CUnIvXD99u4wdExTgD4YxGeXmJTZQyIlUfSz%2Fmtr4vUGZ5lhPnHnqz%2BDszh52ldvkpaOpVchrWJhi7yGGfX1oaCYKcPHfHXuUthfihxKAl13eSUv1fas3Q%3D%3D
//        -----------------
```





