<?php


use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';die;
});

//年度盛典活动
Route::get('richlist','AnnualController/richList');     //年度用户消费排序
Route::get('roomdaylist','AnnualController/roomDayList');       //年度房间日榜排序
Route::get('roomlist','AnnualController/roomList');     //年度房间总榜排序
Route::get('getoptions','ShengyouController/getOptions');     //报名选项
Route::post('signup','ShengyouController/signup');     //报名提交
Route::get('syrank','ShengyouController/syRank');     //声优排行榜
Route::get('wxh5pay','WebAlipayController/wxh5pay');

//公众号
Route::get('gzhindex','WebWxpayController/gzhindex');

//首页
//音恋协议
Route::get('pact','FqpartyController/pact');
Route::get('charge','FqpartyController/charge');
Route::get('czsm','FqpartyController/czsm');
Route::get('master','FqpartyController/master');
Route::get('Minor','FqpartyController/Minor');
Route::get('pay','FqpartyController/pay');
Route::get('fqparty/In','FqpartyController/In');
Route::get('privacy','FqpartyController/Privacy');//隐私2.0
Route::get('registered','FqpartyController/Registered');//注册
Route::get('bouncedText','FqpartyController/bouncedText');//弹窗的文字
Route::get('bounced','FqpartyController/bounced');//弹窗的文字
Route::get('fqparty/vipText','FqpartyController/vipText');//会员协议
Route::get('TheHostIn','FqpartyController/TheHostIn');//主播入驻协议
Route::get('fqparty/dukeDoc','FqpartyController/dukeDoc');//爵位协议
Route::get('fqparty/actionRule','FqpartyController/actionRule');//爵位协议
//mua协议
Route::get('pactNew','FqpartyNewController/pactNew');//用户隐私协议
Route::get('chargeNew','FqpartyNewController/chargeNew');//用户充值协议
Route::get('czsmNew','FqpartyNewController/czsmNew');//充值问题
Route::get('masterNew','FqpartyNewController/masterNew');//动态信息发布准则
Route::get('MinorNew','FqpartyNewController/MinorNew');//未成年保护计划
Route::get('payNew','FqpartyNewController/payNew');//用户支付协议
Route::get('InNew','FqpartyNewController/InNew');//公会主播入驻协议
Route::get('privacyNew','FqpartyNewController/PrivacyNew');//隐私2.0
Route::get('registeredNew','FqpartyNewController/RegisteredNew');//注册
Route::get('bouncedTextNew','FqpartyNewController/bouncedTextNew');//弹窗的文字
Route::get('bouncedNew','FqpartyNewController/bouncedNew');//弹窗的文字
Route::get('vipTextNew','FqpartyNewController/vipTextNew');//会员协议
Route::get('TheHostInNew','FqpartyNewController/TheHostInNew');//主播入驻协议
Route::get('vipDocNew','FqpartyNewController/vipDocNew');       //会员说明
Route::get('vipRuleNew','FqpartyNewController/vipRuleNew');//会员协议
Route::get('autoRenewalRuleNew','FqpartyNewController/autoRenewalRuleNew');//自动续费协议
//音恋协议
Route::get('pactLove','FqpartyLoveController/pactLove');//用户隐私协议
Route::get('chargeLove','FqpartyLoveController/chargeLove');//用户充值协议
Route::get('czsmLove','FqpartyLoveController/czsmLove');//充值问题
Route::get('masterLove','FqpartyLoveController/masterLove');//动态信息发布准则
Route::get('MinorLove','FqpartyLoveController/MinorLove');//未成年保护计划
Route::get('payLove','FqpartyLoveController/payLove');//用户支付协议
Route::get('InLove','FqpartyLoveController/InLove');//公会主播入驻协议
Route::get('privacyLove','FqpartyLoveController/PrivacyLove');//隐私2.0
Route::get('registeredLove','FqpartyLoveController/RegisteredLove');//注册
Route::get('bouncedTextLove','FqpartyLoveController/bouncedText');//弹窗的文字
Route::get('bouncedLove','FqpartyLoveController/bouncedLove');//弹窗的文字
Route::get('vipTextLove','FqpartyLoveController/vipTextLove');//会员协议
Route::get('TheHostInLove','FqpartyLoveController/TheHostInLove');//主播入驻协议
Route::get('dukeDocLove','FqpartyLoveController/dukeDocLove');//爵位协议
Route::get('actionRuleLove','FqpartyLoveController/actionRuleLove');//爵位协议
Route::rule('love','FqpartyLoveController/love');       //首页
Route::get('vipDocLove','FqpartyLoveController/vipDocLove');       //会员说明
Route::get('vipRuleLove','FqpartyLoveController/vipRuleLove');//会员协议
Route::get('autoRenewalRuleLove','FqpartyLoveController/autoRenewalRuleLove');//自动续费协议

//番茄派对语音
Route::get('pactTomato','FqpartyFanqieController/pactTomato');//用户隐私协议
Route::get('chargeTomato','FqpartyFanqieController/chargeTomato');//用户充值协议
Route::get('czsmTomato','FqpartyFanqieController/czsmTomato');//充值问题
Route::get('masterTomato','FqpartyFanqieController/masterTomato');//动态信息发布准则
Route::get('MinorTomato','FqpartyFanqieController/MinorTomato');//未成年保护计划
Route::get('payTomato','FqpartyFanqieController/payTomato');//用户支付协议
Route::get('InTomato','FqpartyFanqieController/InTomato');//公会主播入驻协议
Route::get('PrivacyTomato','FqpartyFanqieController/PrivacyTomato');//隐私2.0
Route::get('RegisteredTomato','FqpartyFanqieController/RegisteredTomato');//注册
Route::get('bouncedTextTomato','FqpartyFanqieController/bouncedTextTomato');//弹窗的文字
Route::get('bouncedTomato','FqpartyFanqieController/bouncedTomato');//弹窗的文字
Route::get('vipTextTomato','FqpartyFanqieController/vipTextTomato');//会员协议
Route::get('TheHostInTomato','FqpartyFanqieController/TheHostInTomato');//主播入驻协议
Route::get('dukeDocTomato','FqpartyFanqieController/dukeDocTomato');//爵位协议
Route::get('actionRuleTomato','FqpartyFanqieController/actionRuleTomato');//爵位协议
Route::rule('Tomato','FqpartyFanqieController/Tomato');       //首页
Route::get('vipDocTomato','FqpartyFanqieController/vipDocTomato');       //会员说明
Route::get('vipRuleTomato','FqpartyFanqieController/vipRuleTomato');//会员协议
Route::get('autoRenewalRuleTomato','FqpartyFanqieController/autoRenewalRuleTomato');//自动续费协议

//音恋语音处cp ios
Route::get('pactCcp','FqpartyYinLinCCPController/pactCcp');//用户隐私协议
Route::get('chargeCcp','FqpartyYinLinCCPController/chargeCcp');//用户充值协议
Route::get('czsmCcp','FqpartyYinLinCCPController/czsmCcp');//充值问题
Route::get('masterCcp','FqpartyYinLinCCPController/masterCcp');//动态信息发布准则
Route::get('MinorCcp','FqpartyYinLinCCPController/MinorCcp');//未成年保护计划
Route::get('payCcp','FqpartyYinLinCCPController/payCcp');//用户支付协议
Route::get('InCcp','FqpartyYinLinCCPController/InCcp');//公会主播入驻协议
Route::get('PrivacyCcp','FqpartyYinLinCCPController/PrivacyCcp');//隐私2.0
Route::get('RegisteredCcp','FqpartyYinLinCCPController/RegisteredCcp');//注册
Route::get('bouncedTextCcp','FqpartyYinLinCCPController/bouncedTextCcp');//弹窗的文字
Route::get('bouncedCcp','FqpartyYinLinCCPController/bouncedCcp');//弹窗的文字
Route::get('vipTextCcp','FqpartyYinLinCCPController/vipTextCcp');//会员协议
Route::get('TheHostInCcp','FqpartyYinLinCCPController/TheHostInCcp');//主播入驻协议
Route::get('dukeDocCcp','FqpartyYinLinCCPController/dukeDocCcp');//爵位协议
Route::get('actionRuleCcp','FqpartyYinLinCCPController/actionRuleCcp');//爵位协议
Route::rule('Ccp','FqpartyYinLinCCPController/Ccp');       //首页
Route::get('vipDocCcp','FqpartyYinLinCCPController/vipDocCcp');       //会员说明
Route::get('vipRuleCcp','FqpartyYinLinCCPController/vipRuleCcp');//会员协议
Route::get('autoRenewalRuleCcp','FqpartyYinLinCCPController/autoRenewalRuleCcp');//自动续费协议

//音恋语音处cp 安卓
Route::get('pactCcp1','FqpartyYinLinCCP1Controller/pactCcp');//用户隐私协议
Route::get('chargeCcp1','FqpartyYinLinCCP1Controller/chargeCcp');//用户充值协议
Route::get('czsmCcp1','FqpartyYinLinCCP1Controller/czsmCcp');//充值问题
Route::get('masterCcp1','FqpartyYinLinCCP1Controller/masterCcp');//动态信息发布准则
Route::get('MinorCcp1','FqpartyYinLinCCP1Controller/MinorCcp');//未成年保护计划
Route::get('payCcp1','FqpartyYinLinCCP1Controller/payCcp');//用户支付协议
Route::get('InCcp1','FqpartyYinLinCCP1Controller/InCcp');//公会主播入驻协议
Route::get('PrivacyCcp1','FqpartyYinLinCCP1Controller/PrivacyCcp');//隐私2.0
Route::get('RegisteredCcp1','FqpartyYinLinCCP1Controller/RegisteredCcp');//注册
Route::get('bouncedTextCcp1','FqpartyYinLinCCP1Controller/bouncedTextCcp');//弹窗的文字
Route::get('bouncedCcp1','FqpartyYinLinCCP1Controller/bouncedCcp');//弹窗的文字
Route::get('vipTextCcp1','FqpartyYinLinCCP1Controller/vipTextCcp');//会员协议
Route::get('TheHostInCcp1','FqpartyYinLinCCP1Controller/TheHostInCcp');//主播入驻协议
Route::get('dukeDocCcp1','FqpartyYinLinCCP1Controller/dukeDocCcp');//爵位协议
Route::get('actionRuleCcp1','FqpartyYinLinCCP1Controller/actionRuleCcp');//爵位协议
Route::rule('Ccp1','FqpartyYinLinCCP1Controller/Ccp');       //首页
Route::get('vipDocCcp1','FqpartyYinLinCCP1Controller/vipDocCcp');       //会员说明
Route::get('vipRuleCcp1','FqpartyYinLinCCP1Controller/vipRuleCcp');//会员协议
Route::get('autoRenewalRuleCcp1','FqpartyYinLinCCP1Controller/autoRenewalRuleCcp');//自动续费协议

//恋音
Route::get('pactLoveSound','FqpartyLoveSoundController/pactLove');//用户隐私协议
Route::get('chargeLoveSound','FqpartyLoveSoundController/chargeLove');//用户充值协议
Route::get('czsmLoveSound','FqpartyLoveSoundController/czsmLove');//充值问题
Route::get('masterLoveSound','FqpartyLoveSoundController/masterLove');//动态信息发布准则
Route::get('MinorLoveSound','FqpartyLoveSoundController/MinorLove');//未成年保护计划
Route::get('payLoveSound','FqpartyLoveSoundController/payLove');//用户支付协议
Route::get('InLoveSound','FqpartyLoveSoundController/InLove');//公会主播入驻协议
Route::get('privacyLoveSound','FqpartyLoveSoundController/PrivacyLove');//隐私2.0
Route::get('registeredLoveSound','FqpartyLoveSoundController/RegisteredLove');//注册
Route::get('bouncedTextLoveSound','FqpartyLoveSoundController/bouncedText');//弹窗的文字
Route::get('bouncedLoveSound','FqpartyLoveSoundController/bouncedLove');//弹窗的文字
Route::get('vipTextLoveSound','FqpartyLoveSoundController/vipTextLove');//会员协议
Route::get('TheHostInLoveSound','FqpartyLoveSoundController/TheHostInLove');//主播入驻协议
Route::get('dukeDocLoveSound','FqpartyLoveSoundController/dukeDocLove');//爵位协议
Route::get('actionRuleLoveSound','FqpartyLoveSoundController/actionRuleLove');//爵位协议
Route::rule('loveSound','FqpartyLoveSoundController/love');       //首页
Route::get('vipDocSound','FqpartyLoveSoundController/vipDoc');       //会员说明

//柔语
Route::get('pactRouYin','FqpartyRouYinController/pactLove');//用户隐私协议
Route::get('chargeRouYin','FqpartyRouYinController/chargeLove');//用户充值协议
Route::get('czsmRouYin','FqpartyRouYinController/czsmLove');//充值问题
Route::get('masterRouYin','FqpartyRouYinController/masterLove');//动态信息发布准则
Route::get('MinorRouYin','FqpartyRouYinController/MinorLove');//未成年保护计划
Route::get('payRouYin','FqpartyRouYinController/payLove');//用户支付协议
Route::get('InRouYin','FqpartyRouYinController/InLove');//公会主播入驻协议
Route::get('privacyRouYin','FqpartyRouYinController/PrivacyLove');//隐私2.0
Route::get('registeredRouYin','FqpartyRouYinController/RegisteredLove');//注册
Route::get('bouncedTextRouYin','FqpartyRouYinController/bouncedText');//弹窗的文字
Route::get('bouncedRouYin','FqpartyRouYinController/bouncedLove');//弹窗的文字
Route::get('vipTextRouYin','FqpartyRouYinController/vipTextLove');//会员协议
Route::get('TheHostInRouYin','FqpartyRouYinController/TheHostInLove');//主播入驻协议
Route::get('dukeDocRouYin','FqpartyRouYinController/dukeDocLove');//爵位协议
Route::get('actionRuleRouYin','FqpartyRouYinController/actionRuleLove');//爵位协议
Route::rule('RouYin','FqpartyRouYinController/love');       //首页

//清清语音
Route::get('pactqq','FqpartyQQController/pactLove');//用户隐私协议
Route::get('chargeqq','FqpartyQQController/chargeLove');//用户充值协议
Route::get('czsmqq','FqpartyQQController/czsmLove');//充值问题
Route::get('masterqq','FqpartyQQController/masterLove');//动态信息发布准则
Route::get('Minorqq','FqpartyQQController/MinorLove');//未成年保护计划
Route::get('payqq','FqpartyQQController/payLove');//用户支付协议
Route::get('Inqq','FqpartyQQController/InLove');//公会主播入驻协议
Route::get('privacyqq','FqpartyQQController/PrivacyLove');//隐私2.0
Route::get('registeredqq','FqpartyQQController/RegisteredLove');//注册
Route::get('bouncedTextqq','FqpartyQQController/bouncedText');//弹窗的文字
Route::get('bouncedqq','FqpartyQQController/bouncedLove');//弹窗的文字
Route::get('vipTextqq','FqpartyQQController/vipTextLove');//会员协议
Route::get('TheHostInqq','FqpartyQQController/TheHostInLove');//主播入驻协议
Route::get('dukeDocqq','FqpartyQQController/dukeDocLove');//爵位协议
Route::get('actionRuleqq','FqpartyQQController/actionRuleLove');//爵位协议
Route::rule('qq','FqpartyQQController/love');       //首页

//楚楚语音
Route::get('pactcc','FqpartyCCController/pactLove');//用户隐私协议
Route::get('chargecc','FqpartyCCController/chargeLove');//用户充值协议
Route::get('czsmcc','FqpartyCCController/czsmLove');//充值问题
Route::get('mastercc','FqpartyCCController/masterLove');//动态信息发布准则
Route::get('Minorcc','FqpartyCCController/MinorLove');//未成年保护计划
Route::get('paycc','FqpartyCCController/payLove');//用户支付协议
Route::get('Incc','FqpartyCCController/InLove');//公会主播入驻协议
Route::get('privacycc','FqpartyCCController/PrivacyLove');//隐私2.0
Route::get('registeredcc','FqpartyCCController/RegisteredLove');//注册
Route::get('bouncedTextcc','FqpartyCCController/bouncedText');//弹窗的文字
Route::get('bouncedcc','FqpartyCCController/bouncedLove');//弹窗的文字
Route::get('vipTextcc','FqpartyCCController/vipTextLove');//会员协议
Route::get('TheHostIncc','FqpartyCCController/TheHostInLove');//主播入驻协议
Route::get('dukeDoccc','FqpartyCCController/dukeDocLove');//爵位协议
Route::get('actionRulecc','FqpartyCCController/actionRuleLove');//爵位协议
Route::rule('cc','FqpartyCCController/love');       //首页



//支付
Route::get('wxzh','WebController/wxzh');
//支付宝支付
//公众号
Route::rule('index','IndexController/index');       //首页
Route::rule('DownloadThe_Android','IndexController/DownloadThe_Android');       //乐嗨嗨
Route::rule('DownloadTheAndroid','IndexController/DownloadTheAndroid');       //乐嗨嗨
Route::rule('music','MusicController/uploadFileMusic');       //音乐上传
Route::rule('musicList','MusicController/musicList');       //音乐列表
Route::rule('uploadFile','MusicController/uploadFile');       //音乐上传0SS接口
Route::rule('smsCode','MemberController/smsCode');       //短信发送
Route::rule('login','MemberController/login');       //登录接口
Route::get('loginOut', 'MemberController/loginOut');//退出
Route::get('statusMusic', 'MusicController/statusMusic');          //上下架修改

Route::post('doAppCount', 'IndexController/doAppCount');        //点击量统计
//Route::rule('check', 'WechatController/check');        //微信公众号验证
Route::rule('check', 'WechatController/wxIndex');        //微信公众号回复消息
Route::get('accessToken', 'WechatController/getAccessToken');        //微信公众号验证token
Route::get('defaultMenu', 'WechatController/createMenu');        //微信公众号创建默认菜单
Route::get('delMenu', 'WechatController/delMenu');        //微信公众号删除菜单
Route::get('iosMenu', 'WechatController/createIosMenu');        //微信公众号创建ios个性化菜单
Route::get('androidMenu', 'WechatController/createAndroidMenu');        //微信公众号创建安卓个性化菜单
Route::get('indexPlatform','AppAwakenController/indexPlatform');        //分享页面
Route::get('indexPlatformMua','AppAwakenController/indexPlatformMua');        //mua分享页面
Route::get('double','FqpartyController/double');        //双12活动页面
Route::get('ghzm','FqpartyController/ghzm');        //工会招募页面


//Route::rule('check1', 'WechatMuaController/check');        //微信公众号验证
Route::rule('check1', 'WechatMuaController/wxIndex');        //微信公众号回复消息
Route::rule('accessToken1', 'WechatMuaController/getAccessToken');        //微信公众号验证token
Route::rule('defaultMenu1', 'WechatMuaController/createMenu');        //微信公众号创建默认菜单
Route::rule('delMenu1', 'WechatMuaController/delMenu');        //微信公众号删除菜单
Route::rule('iosMenu1', 'WechatMuaController/createIosMenu');        //微信公众号创建ios个性化菜单
Route::rule('androidMenu1', 'WechatMuaController/createAndroidMenu');        //微信公众号创建安卓个性化菜单

//音恋语音app公众号
Route::rule('check2', 'WechatYinLianController/wxIndex');        //微信公众号回复消息
Route::rule('accessToken2', 'WechatYinLianController/getAccessToken');        //微信公众号验证token
Route::rule('defaultMenu2', 'WechatYinLianController/createMenu');        //微信公众号创建默认菜单
Route::rule('delMenu2', 'WechatYinLianController/delMenu');        //微信公众号删除菜单
Route::rule('iosMenu2', 'WechatYinLianController/createIosMenu');        //微信公众号创建ios个性化菜单
Route::rule('androidMenu2', 'WechatYinLianController/createAndroidMenu');        //微信公众号创建安卓个性化菜单


//音恋语音app公众号
Route::rule('check3', 'WechatYinkaController/wxIndex');        //微信公众号回复消息
Route::rule('accessToken3', 'WechatYinkaController/getAccessToken');        //微信公众号验证token
Route::rule('defaultMenu3', 'WechatYinkaController/createMenu');        //微信公众号创建默认菜单
Route::rule('delMenu3', 'WechatYinkaController/delMenu');        //微信公众号删除菜单
Route::rule('iosMenu3', 'WechatYinkaController/createIosMenu');        //微信公众号创建ios个性化菜单
Route::rule('androidMenu3', 'WechatYinkaController/createAndroidMenu');        //微信公众号创建安卓个性化菜单
Route::get('getJsSdkParams', 'WechatYinkaController/getJsSdkParams');        //获取公众号配置信息

Route::post('pubic/openinstall', 'OpenInstallController/bindOpeninstall');
Route::post('pubic/refereeinfo', 'OpenInstallController/getRefereeInfo');
Route::post('pubic/refereeRoomInfo', 'OpenInstallController/getRefereeRoomInfo');        //微信公众号创建安卓个性化菜单
Route::get('xingTuCallBack', 'OpenInstallController/xingTuCallBack');        //星图回调写入数据


Route::get('getUrl', 'IndexController/getUrl');        //获取公众号中的h5地址

Route::group('wechat', function () {
//    公众号提现相关
    Route::rule('sendsms', 'wechat.UserLoginController/sendsms');   //发短信
    Route::rule('login', 'wechat.UserLoginController/login');   //登录
    Route::rule('userInfo', 'wechat.WithdrawController/userInfo');   //用户信息
    Route::rule('identityInfo', 'wechat.WithdrawController/identityInfo');   //获取身份认证信息
    Route::rule('storeWithDrawUserInfo', 'wechat.WithdrawController/storeWithDrawUserInfo');   //提交用户的提现认证信息
    Route::rule('withDrawBankShow', 'wechat.WithdrawController/withDrawBankShow');   //查看所有提现账号
    Route::rule('withDrawBankStore', 'wechat.WithdrawController/withDrawBankStore');   //添加提现账号
    Route::rule('withDrawBankDelete', 'wechat.WithdrawController/withDrawBankDelete');   //删除提现账号
    Route::rule('withDrawBankSetDefault', 'wechat.WithdrawController/withDrawBankSetDefault');   //设为默认账号
    Route::rule('withDrawApply', 'wechat.WithdrawController/withDrawApply');   //提交提现申请
    Route::rule('withDrawOrderList', 'wechat.WithdrawController/withDrawOrderList');   //展示用户的订单列表
})->middleware([\app\middleware\LimitFlowWeb::class,\app\middleware\ResponseLog::class, \app\middleware\BaseRawLog::class]);
