<?php


use think\facade\Route;

Route::group('v1', function () {
    Route::rule('getH5Url', 'v1.InitDataController/getUrl');
    Route::rule('agora/getToken', 'v1.AgoraController/getToken');
    Route::rule('agora/getRoomUserList', 'v1.AgoraController/getRoomUserList');
    Route::get('testdata', 'v1.AppDataController/testdata');
    Route::get('userData', 'v1.AppDataController/userData');
    Route::get('getApiUrl', 'v1.AppDataController/getApiUrl');
    Route::get('huodong11', 'v1.AppDataController/huodong11');
    Route::get('huodong111', 'v1.AppDataController/huodong111');
    Route::get('testgame', 'v1.AppDataController/testGame');
    Route::post('delMonitoringData', 'v1.MonitoringController/delMonitoringData');//青少年模式

    Route::post('setp2ptoken', 'v1.MqttTokenController/setp2pToken');//更新p2pMQtoken
    Route::post('setptoken', 'v1.MqttTokenController/setp2pToken');//更新p2pMQtoken
    Route::post('verifymqtttoken', 'v1.MqttTokenController/verifyToken');//验证MQtoken
    Route::post('delmqtttoken', 'v1.MqttTokenController/revokeToken');//撤销MQtoken
    Route::post('sendpmsg', 'v1.MqttMessageController/p2pMsg');//p2p消息
    Route::get('initList', 'v1.InitDataController/initList');    //初始化广告接口
//    Route::rule('androidActivate', 'v1.AppDataController/androidActivate');  // 安卓设备激活

    //用户相关
    Route::get('checkbindmobile', 'v1.UserLoginController/checkbindMobile');    //检查绑定手机号
    Route::post('userbindmobile', 'v1.UserLoginController/userBindMobile');    //新用户绑定手机号
    Route::post('canceluser', 'v1.UserLoginController/cancelUser');    //注销用户
    Route::post('reportUser', 'v1.MemberController/reportUser');    //举报用户(老版)
    Route::rule('complaintUser', 'v1.UserInfoController/complaintUser');    //举报用户(新版本)
    Route::post('verifymobile', 'v1.MemberController/verifyMobile');    //验证换绑手机验证码
    Route::post('changemobile', 'v1.MemberController/setMobile');    //更新手机号
    Route::post('login', 'v1.MemberController/login');    //用户登录接口
    Route::post('userlogin', 'v1.UserLoginController/login');    //用户登录
    Route::post('perfectuserinfo', 'v1.UserLoginController/perfectUserInfo');    //完善信息
    Route::get('thirdinfo', 'v1.UserLoginController/thirdInfo');    //三方用户信息
    Route::post('userregister', 'v1.UserLoginController/regist');    //用户注册
    Route::post('register', 'v1.MemberController/register');    //用户注册接口
    Route::rule('autologin', 'v1.UserLoginController/autologin');    //用户自动登录接口
    Route::post('editUser', 'v1.MemberController/edit');    //用户修改信息接口
    Route::post('setpassword', 'v1.MemberController/setPassword');//设置密码
    Route::post('forgetpassword', 'v1.MemberController/forgetPassword');//忘记密码
    Route::post('setalbum', 'v1.MemberAvatarController/setAlbum');    //用户上传相册接口
    Route::post('setavatar', 'v1.MemberAvatarController/setAvatar');    //用户上传头像
    Route::post('initmymoney', 'v1.WalletController/initMyMoney');    //initMyMoney
    Route::get('initmymoney', 'v1.WalletController/initMyMoney');    //initMyMoney
    Route::post('setHiddenOnline', 'v1.UserInfoController/setHiddenOnline');    //设置隐藏在线状态
    Route::get('getHiddenInfo', 'v1.UserInfoController/getHiddenInfo');    // 隐私信息

    //动态相关
    Route::get('forumTagList', 'v1.ForumController/forumTagList');    //动态话题列表接口
    Route::get('forumList', 'v1.ForumController/forumList'); //动态列表

    //房间相关
    Route::get('roomList', 'v1.LanguageroomController/roomList');    //首页房间列表接口
    Route::get('newRoomListLite', 'v1.LanguageroomController/newRoomListLite');    //首页房间列表接口新版 lite
    Route::post('createroom', 'v1.LanguageroomController/CreateRoom');//创建房间
    Route::get('taglist', 'v1.LanguageroomController/createRoomTagList');//房间标签列表
    Route::get('newTagList', 'v1.LanguageroomController/createRoomTagNewList');//新房间标签列表
    Route::get('followlist', 'v1.RoomFollowController/followList');  //用户关注房间列表
    Route::post('attentionroom', 'v1.RoomFollowController/attentionRoom');  //用户关注房间
    Route::get('removeattentionroom', 'v1.RoomFollowController/removeRoom');  //用户取消关注房间
    Route::post('setuserinfo', 'v1.RoomFollowController/setUserinfo');  //隐身用户设置
    Route::get('invisuser', 'v1.RoomFollowController/invisUser');  //隐身用户
    Route::get('gameroommatch', 'v1.RoomRecommendController/gameRoomMatch');  //首页匹配游戏房间


    Route::rule('roomPhotoList', 'v1.RoomPhotoController/roomPhotoList');  //房间相册列表
    Route::rule('uploadPhotoConf', 'v1.RoomPhotoController/uploadPhotoConf');  //房间相册上传照片配置
    Route::rule('addPhoto', 'v1.RoomPhotoController/addPhoto');  //添加房间相册
    Route::rule('removePhoto', 'v1.RoomPhotoController/removePhoto');  //删除房间相册
    Route::rule('removeAllPhoto', 'v1.RoomPhotoController/removeAllPhoto');  //一键删除房间相册
    Route::rule('unlockPhoto', 'v1.RoomPhotoController/unlockPhoto');  //解锁房间相册
    Route::rule('synPublicScreen', 'v1.RoomPhotoController/synPublicScreen');  //同步房间相册到公屏
    Route::rule('unlockPhotoList', 'v1.RoomPhotoController/unlockPhotoList');  //获取解锁房间相册列表

    //支付接口
    Route::post('apppay', 'v1.PayController/AppPay');//app支付
    Route::post('paymentnotify', 'v1.PayController/AppAlipayNotify');//app支付宝异步回调
    Route::post('apppay', 'v1.PayController/AppAlipayReturn');//app支付宝同步
    Route::rule('wxp', 'v1.WxwbPayController/weixinPay');         //微信支付
    Route::rule('wxpnotify', 'v1.WxwbPayController/wxWebNotify');     //支付回调
    Route::post('applepay', 'v1.ApplePayController/payment');     //苹果支付
    Route::rule('payment', 'v1.PayController/PayMent');     //支付
    Route::rule('autoSignAliNotify', 'v1.PayNotifyController/autoSignAliNotify'); // 支付宝 vip自动续费回调地址
    Route::rule('autoSignAppleNotify', 'v1.PayNotifyController/autoSignAppleNotify'); // 苹果 vip自动续费回调地址

//    新版支付接口相关
    Route::post('iosBuyProduct', 'v1.ApplePayController/iosBuyProduct');//ios创建订单
    Route::post('AndroidBuyProduct', 'v1.PayController/AndroidBuyProduct');//安卓创建订单 (PayMent)
    Route::post('iosPayMent', 'v1.ApplePayController/iosPayMent');//ios发货 (payment)
    Route::rule('walletBanner', 'v1.WalletController/walletBanner');//充值banner  ()
    Route::rule('appStoreProductList', 'v1.ApplePayController/appStoreProductList');     //ios充值arealist （）
    Route::rule('walletDetails', 'v1.OrderController/walletDetails');//账单明细  ()
    Route::rule('payChannel', 'v1.PayController/payChannel');// 支付渠道
    Route::rule('chinaumsNotify', 'v1.PayNotifyController/chinaumsNotify'); // 三方支付-银联商务回调地址
    Route::rule('getWxAppletUrlLink', 'v1.PayController/getWxAppletUrlLink'); // 生成小程序链接地址(三方支付-银联商务) H5跳转小程序使用

    Route::get('checkfirstpay', 'v1.FirstPayController/checkFirstPay');     //首冲弹窗
    Route::get('checkfirstpaypop', 'v1.FirstPayController/checkFirstPayPop');     //首冲弹窗
    Route::rule('firstChargePop', 'v1.FirstPayController/firstChargePop'); # 是否弹首充
    Route::rule('firstChargeData', 'v1.FirstPayController/firstChargeData'); # 首充
    Route::get('firstPayInfo', 'v1.Activity.FirstPayController/firstPayInfo');     //首冲弹窗info

    Route::get('getFirstChargeReward', 'v1.FirstPayController/getFirstChargeReward'); // 首充奖励
    Route::get('getFirstChargePop', 'v1.FirstPayController/getFirstChargePop');      // 首充弹框
    Route::post('receiveFirstChargeReward', 'v1.FirstPayController/receiveFirstChargeReward'); // 首充成功领取奖励

    //猜拳礼物
    Route::get('cqgift', 'v1.GiftController/cqGift');

    //活动相关
    Route::get('activelist', 'v1.InitDataController/activeList');    //活动接口

    //公会相关
    Route::get('guildlist', 'v1.MemberGuildController/guildList');    //审请公会列表接口
    Route::get('guilddetail', 'v1.MemberGuildController/guildDetail');    //公会详情接口
    Route::post('guildadd', 'v1.MemberGuildController/guildAdd');    //加入公会接口(老接口)
    Route::post('addGuild', 'v1.MemberGuildController/addGuild');    //加入公会接口(新接口)
    Route::post('cancelGuild', 'v1.MemberGuildController/cancelGuild');    //加入公会接口

    /* 公会相关 */
    Route::get('searchGuild', 'v1.MemberGuildController/searchGuild'); //搜索公会
    Route::get('userGuild', 'v1.MemberGuildController/userGuild'); //用户申请/加入的公会
    Route::post('operationGuild', 'v1.MemberGuildController/operationGuild'); //用户改变公会关系

    Route::post('breakbox', 'v1.BoxController/breakBox');//开宝箱
    Route::post('buykey', 'v1.BoxController/buyKey');//买钥匙
    Route::get('rankboxlist', 'v1.BoxController/rankBoxList');//宝箱排行
    Route::get('boxinit', 'v1.BoxController/boxInit');//宝箱进度
    Route::get('boxgiftpool', 'v1.BoxController/boxGiftPool');//奖池说明
    Route::get('boxintroduce', 'v1.BoxController/boxIntroduce');//奖池礼物

    ///访客
    Route::get('visitorlist', 'v1.VisitorController/getList');  //最近访客
    Route::post('setHiddenVisitor', 'v1.VisitorController/setHiddenVisitor');  // 设置隐身访问
    Route::get('getHiddenVisitorList', 'v1.VisitorController/getHiddenVisitorList');  // 隐身访问列表

    //我的信息
    Route::get('userinfo', 'v1.UserInfoController/userinfo');
    Route::get('usergift', 'v1.UserInfoController/usergiftlist');
    Route::get('userbyid', 'v1.UserInfoController/userByid');
    Route::get('userGiftMap', 'v1.UserInfoController/userGiftMap');
    Route::get('userGiftWall', 'v1.UserInfoController/userGiftWall');
    Route::get('userGiftCollection', 'v1.UserInfoController/userGiftCollection');
    Route::rule('defaultNickname', 'v1.UserInfoController/defaultNickname');    //获取昵称list  ()
    Route::get('getVoiceDocument', 'v1.UserInfoController/getVoiceDocument');    //获取语音介绍文案
    Route::get('fansmenusatatus', 'v1.UserInfoController/fansmenusatatus'); //粉丝开播提醒按钮 安卓使用

    //app信息
    Route::get('iosdata', 'v1.AppDataController/iosdata');
    Route::get('rydata', 'v1.AppDataController/reyun');
    Route::get('checkupdate', 'v1.AppDataController/checkUpdate');
    Route::get('appdata', 'v1.AppDataController/getAppData');
    Route::get('checkidfa', 'v1.AppDataController/checkidfa');
    Route::get('returnBaseConfig', 'v1.InitDataController/returnBaseConfig');
    Route::get('returnBaseConfig2', 'v1.InitDataController/returnBaseConfig2');

    //相册删除操作
    Route::get('delalbum', 'v1.MemberAvatarController/delAlbum');

    //充值列表
    Route::get('chargeRecordList', 'v1.CoinController/chargeList');
    Route::rule('chargeListCoin', 'v1.CoinController/chargeListCoin');//豆兑换金币列表

    //用户背包列表
    Route::get('userpacklist', 'v1.UserInfoController/userpacklist');
    Route::get('newUserPackList', 'v1.UserInfoController/newUserPackList');
    Route::get('packList', 'v1.UserInfoController/userpacklist');    //私聊背包数据

    //用户背包列表
    Route::get('sendgift', 'v1.SendGiftController/sendgift');

    //房间消息
    Route::get('noticemsglist', 'v1.NoticeMsgController/noticeList');
    Route::get('noticeNewList', 'v1.NoticeMsgController/noticeNewList');

    //房间管理员列表
    Route::get('managerList', 'v1.RoomManagerController/managerList');
    //添加房间管理员
    Route::post('addManager', 'v1.RoomManagerController/addManager');
    //取消房间管理员
    Route::rule('removeManager', 'v1.RoomManagerController/removeManager');
    //搜索管理员
    Route::get('searchManager', 'v1.RoomManagerController/searchManager');
    //房间修改功能
    Route::post('saveRoom', 'v1.LanguageroomController/saveRoom');
    //房间修改功能新版api
    Route::post('saveRoomLite', 'v1.LanguageroomController/saveRoomLite');
    //房间黑名单列表
    Route::get('roomBlackList', 'v1.RoomBlackController/roomBlackList');
    //添加房间黑名单
    Route::post('addBlackUser', 'v1.RoomBlackController/addBlackUser');
    //取消房间黑名单
    Route::get('delBlackUser', 'v1.RoomBlackController/delBlackUser');
    //搜索房间黑名单
    Route::get('findBlackUser', 'v1.RoomBlackController/findBlackUser');

    //首页推荐房间
    Route::get('hotRoom', 'v1.LanguageroomController/hotRoom');
    //房间加锁解锁
    Route::post('lockRoom', 'v1.LanguageroomController/lockRoom');
    //房间设置
    Route::get('roomInfo', 'v1.LanguageroomController/roomInfo');
    //房间设置新版本
    Route::get('roomInfoLite', 'v1.LanguageroomController/roomInfoLite');

    //搜索
    Route::get('searchmore', 'v1.SearchController/searchmore');
    Route::get('searchclear', 'v1.SearchController/searchclear');

    //礼物列表
    Route::get('giftList', 'v1.GiftController/giftList');
    Route::get('newGiftList', 'v1.GiftController/newGiftList');
    Route::get('giftgamelist', 'v1.GiftController/giftGameList');//游戏礼物列表
    Route::get('msggiftlist', 'v1.GiftController/msgGiftList');//消息礼物列表
    Route::rule('giftBoxRank', 'v1.GiftController/giftBoxRank'); # 盲盒榜单
    Route::rule('giftBoxRoll', 'v1.GiftController/giftBoxRoll'); # 盲盒滚动
    // 动画礼物地址mp4列表
    Route::get('giftMp4AnimationList', 'v1.GiftController/giftMp4AnimationList');
    //表情列表
    Route::get('emojiList', 'v1.EmoticonController/getList');
    //房间背景列表
    Route::get('photoWall', 'v1.LanguageroomController/photoWall');
    //房间派对列表lite版
    Route::get('partyRoomListLite', 'v1.LanguageroomController/partyRoomListLite');
    //房间背景设置
    Route::post('saveRoomWall', 'v1.LanguageroomController/saveRoomWall');

    //送礼
    Route::post('openBagGift', 'v1.SendGiftController/openBagGift'); //打开礼物
    Route::post('sendroomgift', 'v1.SendGiftController/sendRoomGift');
    Route::post('delcharm', 'v1.SendGiftController/delCharm');//清空魅力值

    //粉丝贡献榜
    Route::post('fansRankList', 'v1.RankListController/fansRankList');
    //房间分类
    Route::get('roomTypeList', 'v1.LanguageroomController/roomTypeList');
    //双清禁言踢出
    Route::get('clearblack', 'v1.RoomBlackController/clearblack');
    //禁言 取消禁言
    Route::post('estoppel', 'v1.RoomBlackController/estoppel');
    //发送短信
    Route::post('sendsms', 'v1.SmsController/sendsmsLite');

    //app支付
    Route::post('payment', 'v1.PayController/PayMent');
    Route::get('appchargelist', 'v1.PayController/appChargeList');
    Route::rule('appalinotify', 'v1.PayNotifyController/appAliNotify');
    Route::rule('appalinotifymua', 'v1.PayNotifyController/appAliNotifyMua');
    Route::rule('appwxnotify', 'v1.PayNotifyController/appWxNotify');
    Route::rule('appaliyinliannotify', 'v1.PayNotifyController/appAliYinlianNotify');
    Route::rule('zgwxnotify', 'v1.PayNotifyController/zgWxNotify');

    //用户音乐
    Route::get('musicList', 'v1.MusicController/getList');       //音乐列表
    Route::get('likeList', 'v1.MusicController/likeList');       //收藏音乐列表
    Route::post('addMusic', 'v1.MusicController/addMusic');      //收藏音乐
    Route::rule('delMusic', 'v1.MusicController/delMusic');      //删除音乐
    Route::get('searchMusic', 'v1.MusicController/searchMusic');    //搜索音乐
    Route::get('addPlay', 'v1.MusicController/addPlay');    //音乐播放

    Route::get('chartsAdd', 'v1.InitDataController/chartsAdd');    //统计埋点数据

    //装扮
    Route::get('attireinit', 'v1.AttireController/attireInit');//装扮init
    Route::get('attiretype', 'v1.AttireController/attireType');//装扮分类
    Route::get('newAttireList', 'v1.AttireController/newAttireList');//装扮列表
    Route::get('selfattire', 'v1.AttireController/selfAttire');//我的装扮
    Route::post('buyattire', 'v1.AttireController/buyAttire');//购买装扮
    Route::post('setattire', 'v1.AttireController/setAttire');//更新装扮
    Route::rule('attireAction', 'v1.AttireController/attireAction'); //道具使用、分解礼物
    Route::rule('newSelfAttire', 'v1.AttireController/newSelfAttire');//我的装扮
    Route::rule('newSetAttire', 'v1.AttireController/newSetAttire');//更新装扮

    //帖子
    Route::rule('report', 'v1.ReportController/report');               //动态举报
    Route::post('option', 'v1.ReportController/option');               //动态举报选项
    Route::get('praise', 'v1.ReportController/enjoy');               //动态点赞
    Route::get('forumEnjoyList', 'v1.ReportController/forumEnjoyList');               //我动态被点赞的消息列表新版
    Route::get('forumEnjoyPeopleList', 'v1.ReportController/forumEnjoyPeopleList');               //我动态点赞的人列表新版

    Route::post('addforum', 'v1.ForumController/addforum');      //发表帖子
    Route::get('ownForumList', 'v1.ForumController/selfforumList');      //用户自己帖子
    Route::get('newOwnForumList', 'v1.ForumController/newSelfForumList');      //用户自己帖子
    Route::get('selfForumImageList', 'v1.ForumController/selfForumImageList');      //用户自己帖子
    Route::get('shareForum', 'v1.ForumController/shareForum');      //分享帖子统计
    Route::post('delForum', 'v1.ForumController/delforum');               //动态删除
    Route::get('msgReplyList', 'v1.ForumController/msgReplyList');               //我动态被评论的消息列表
    Route::get('forumDetailMsg', 'v1.ForumController/repayDetail');               //动态评论已未读详情
    Route::post('forumAddReply', 'v1.ForumController/addreply');   //评论
    Route::post('forumdetail', 'v1.ForumController/forumdetail');   //动态详情
    Route::post('replylist', 'v1.ForumController/replylist');
    Route::post('setFeedback', 'v1.AppDataController/setFeedback');   //意见反馈
    Route::post('setUserForumTop', 'v1.ForumController/setUserForumTop');   // 设置动态置顶

    //关注
    Route::get('clearAllMsg', 'v1.AttentionController/clearAllMsg');          //忽略未读信息接口
    Route::get('clearUserMsg', 'v1.AttentionController/clearUserMsg');          //粉丝已读信息接口
    Route::post('careusergroup', 'v1.AttentionController/careUserGroup');               //批量关注用户
    Route::get('callList', 'v1.AttentionController/callList');               //打招呼列表
    Route::get('userMsgCount', 'v1.AttentionController/userMsgCount');               //消息统计
    Route::get('careUserList', 'v1.AttentionController/careUserList');               //关门,粉丝,好友列表
    Route::get('searchFriend', 'v1.AttentionController/searchFriend');               //搜索好友
    Route::get('msgList', 'v1.AttentionController/msgList');               //消息列表
    Route::post('setUserRemark', 'v1.AttentionController/setUserRemark');    // 设置用户备注

    // 特别关心
    Route::get('getSpecialCareList', 'v1.UserSpecialCareController/getSpecialCareList');    // 特别关心列表
    Route::post('setSpecialCare', 'v1.UserSpecialCareController/setSpecialCare');    // 设置特别关心


    //青少年模式
    Route::post('switchmonitor', 'v1.MonitoringController/switchMonitor');
    Route::post('setmonitor', 'v1.MonitoringController/setMonitor');
    Route::get('checkteen', 'v1.MonitoringController/checkTeen');
    Route::post('renewaltime', 'v1.MonitoringController/renewalTime');
    Route::get('monitortime', 'v1.MonitoringController/monitorTime');
    Route::post('resetMonitor', 'v1.MonitoringController/resetMonitor');
    Route::get('queryMonitor', 'v1.MonitoringController/queryMonitor');

    //获取房间类型
    Route::get('roomTypeStatus', 'v1.LanguageroomController/roomTypeStatus');
    Route::get('editRoomType', 'v1.LanguageroomController/editRoomType');        //修改房间
    Route::rule('reportRoom', 'v1.LanguageroomController/reportRoom');        //修改房间


    //vip会员
    Route::get('vipPrivilege', 'v1.VipController/privilegeList');       //会员特权列表
    Route::get('vipChargeInit', 'v1.VipController/vipChargeInit');       // 会员列表
    Route::get('vipChargeInitSecond', 'v1.VipController/vipChargeInitSecond');    // 新版会员支付弹框
    Route::post('appVipPayment', 'v1.PayController/appVipPayment');      // 安卓会员支付
    Route::rule('appvipwxnotify', 'v1.PayNotifyController/appVipWxNotify');      //微信会员支付回调
    Route::rule('appvipalinotify', 'v1.PayNotifyController/appVipAliNotify');      //支付宝会员支付回调
    Route::post('chargePayment', 'v1.ApplePayController/chargePayment');      //苹果会员支付
    Route::rule('vipBuyDetails', 'v1.OrderController/vipBuyDetails');// vip账单

    //个推接口
    Route::post('pushToSingle', 'v1.PushOfGetuiController/pushToSingle');
    Route::post('pushToList', 'v1.PushOfGetuiController/pushToList');

    //拉黑列表
    Route::get('blackList', 'v1.AttentionController/blackList');
    Route::post('addblock', 'v1.AttentionController/addBlackUser');//拉黑用户
    Route::post('delblock', 'v1.AttentionController/delBlackUser');//取消拉黑

    //红包
    Route::get('packetsnew', 'v1.RedPacketsController/packetsNew');
    Route::get('redpacketsinit', 'v1.RedPacketsController/redPacketsInit');
    Route::post('sendpackets', 'v1.RedPacketsController/sendPackets');
    Route::post('getredpackets', 'v1.RedPacketsController/getRedPackets');
    Route::get('packetsnum', 'v1.RedPacketsController/PacketsNum');
    Route::get('packetsdetail', 'v1.RedPacketsController/PacketsDetail');
    Route::rule('alipackets', 'v1.PayNotifyController/alipackets');
    Route::rule('alipacketsmua', 'v1.PayNotifyController/alipacketsMua');
    Route::rule('wxpackets', 'v1.PayNotifyController/wxpackets');
    Route::rule('packetspayment', 'v1.ApplePayController/packetsPayment');

    //IM消息
//    Route::get('checkMessage', 'v1.ImController/imCheck');   //im图片检测
    Route::get('checkMessage', 'v1.ImController/imCheckSecond');   //im图片检测
    Route::post('imMessageWithdraw', 'v1.ImController/imMessageWithdraw');   //消息撤回

    //im 资源相关
    Route::get('getImResourceList', 'v1.ImResourceController/getImResourceList');   //  im气泡、背景、表情包列表
    Route::post('setImBackground', 'v1.ImResourceController/setImBackground');   //  设置聊天背景
    Route::post('setImBubble', 'v1.ImResourceController/setImBubble');   //  设置聊天气泡
    Route::post('setImEmotion', 'v1.ImResourceController/setImEmotion');   //  设置表情包
    Route::rule('getUserImRelated', 'v1.ImResourceController/getUserImRelated');   //  获取用户聊天信息相关(im气泡、背景、表情包)

    //任务
    Route::get('weeksignpop', 'v1.TaskController/weekSignPop');//周签到弹窗
    Route::post('weeksign', 'v1.TaskController/weekSign');//签到
    Route::post('activebox', 'v1.TaskController/activeBox');//活跃度领取
    Route::post('gettask', 'v1.TaskController/getTask');//任务领取
    Route::get('taskcenter', 'v1.TaskController/taskCenter');//任务中心
    //重构任务
    Route::post('setshare', 'v1.TaskController2/setshare');//分享回调
    Route::get('ishavetask', 'v1.TaskController2/ishavetask');//是否有任务
    Route::get('weeksignpop2', 'v1.TaskController2/weekSignPop');//周签到弹窗
    Route::post('weeksign2', 'v1.TaskController2/weekSign');//签到
    Route::post('activebox2', 'v1.TaskController2/activeBox');//活跃度领取
    Route::post('gettask2', 'v1.TaskController2/getTask');//任务领取
    Route::get('taskcenter2', 'v1.TaskController2/taskCenter');//任务中心

    Route::get('goldcoinboxinit', 'v1.GoldcoinBoxController/goldcoinBoxInit');//金币抽奖初始化
    Route::post('goldcoinbox', 'v1.GoldcoinBoxController/goldcoinBox');//金币抽奖
    Route::get('goldcoinboxlog', 'v1.GoldcoinBoxController/goldcoinBoxLog');//金币抽奖记录

    Route::get('goldmallinit', 'v1.GoldMallController/goldcoinMallInit');//金币商城初始化
    Route::post('goldmall', 'v1.GoldMallController/goldcoinMall');//金币商城
    Route::get('goldmalllog', 'v1.GoldMallController/goldcoinMallLog');//金币抽商城记录

    //等级特权
    Route::get('levelPrivilegeList', 'v1.MemberLevel/levelPrivilegeList');//等级特权


    Route::post('newAddMusic', 'v1.MusicController/newAddMusic');//新版房间添加音乐
    Route::get('newDelMusic', 'v1.MusicController/newDelMusic');//新版房间删除音乐
    Route::get('newLikeList', 'v1.MusicController/newLikeList');//新版用户音乐收藏列表
    Route::get('newMusicList', 'v1.MusicController/newGetList');//新版用户音乐上传列表
    Route::get('newSearchMusic', 'v1.MusicController/newSearchMusic');//新版搜索音乐管理


    Route::get('incomeNewDetails', 'v1.OrderController/incomeNewDetails');//新版收入明细
    Route::get('expendDetails', 'v1.OrderController/expendDetails');//新版消费明细

    Route::post('userOnline', 'v1.AppDataController/userOnline');//用户在线时长
    Route::post('userRoomOnline', 'v1.AppDataController/userRoomOnline');//用户房间在线时长
    Route::post('userOnlineHeartBeat', 'v1.AppDataController/userOnlineHeartBeat');   //用户在线心跳接口

    Route::get('getOnlineList', 'v1.MemberRecommendController/getOnlineList');//在线用户列表
    Route::get('getOnlineNewList', 'v1.MemberRecommendController/getOnlineNewList');//在线用户列表新版
    Route::get('onlineUser', 'v1.MemberRecommendController/onlineUser');//在线用户列表新版
    Route::get('TakeShot', 'v1.AttentionController/TakeShot');//拍一拍

    Route::get('getCpImage', 'v1.MemberRecommendController/getCpImage');//cp匹配图片
    Route::get('greet', 'v1.MemberRecommendController/greet');//打招呼
    Route::rule('myRoom', 'v1.LanguageroomController/myRoom');//我的房间
    Route::get('newHotRoom', 'v1.LanguageroomController/newHotRoom');//新版热门房间

    Route::post('loginFeedBack', 'v1.UserLoginController/loginFeedBack');//登陆反馈
    Route::post('imMessageNotify', 'v1.ImNotifyController/imMessageNotify');//Im消息回调
    Route::get('giftBoxInfo', 'v1.AppDataController/giftBoxInfo');//礼物盒子规则
    Route::get('memberIdentityInit', 'v1.MemberIdentity/memberIdentityInit');//身份认证init
    Route::get('queryIdentity', 'v1.MemberIdentity/queryIdentity');//查询身份认证结果
    Route::get('reCallReWard', 'v1.UserLoginController/reCallReWard');//回归礼物

    //banner
    Route::rule('led/linkUrl', 'v1.BannerController/getLedJumpUrl');//获取led跳转地址

    //游戏礼物接口
    Route::get('hallinfo', 'v1.GiftGameController/HallInfo');//大厅初始化
    Route::get('gameranklist', 'v1.GiftGameController/GameRankList');//排行榜
    Route::get('gameinfo', 'v1.GiftGameController/GameInfo');//游戏初始化
    Route::get('gamebrocast', 'v1.GiftGameController/gamebroCast');//游戏滚动
    Route::get('gamerewards', 'v1.GiftGameController/GameRewards');//游戏奖励列表
    Route::get('gameexchangelist', 'v1.GiftGameController/GameExchangeList');//兑换列表
    Route::post('gameaction', 'v1.GiftGameController/GameAction');//掷骰子
    Route::post('gameexchange', 'v1.GiftGameController/GameExchange');//兑换礼物
    Route::post('selfnum', 'v1.GiftGameController/selfNum');//次数

    Route::get('inintexchange', 'v1.WalletController/inintExchange');//钱包收入
    Route::rule('initBeanExchange', 'v1.WalletController/initBeanExchange');//豆兑换初始化
    Route::post('diamondexchanggecoin', 'v1.WalletController/diamondExchangeCoin');//钻石兑换
    Route::rule('beanchanggecoin', 'v1.WalletController/beanchanggecoin');//豆兑换金币

    Route::get('dukeinit', 'v1.DukeController/dukeInit');//dukeinit
    Route::get('dukeinfo', 'v1.DukeController/dukeInfo');//dukeinfo

    Route::post('problemList', 'v1.ProblemController/getList');//问题列表


    Route::post('checkMonitoringStatus', 'v1.MonitoringController/checkMonitoringStatus');//

    Route::post('iosChargeList', 'v1.ApplePayController/iosChargeList');//
    Route::rule('iosPayNotice', 'v1.ApplePayController/iosPayNotice');//
    Route::post('androidChargeList', 'v1.OrderController/androidChargeList');//

    Route::get('getStsToken', 'v1.AppDataController/getStsToken');//oss授权访问
    Route::post('getStsToken', 'v1.AppDataController/getStsToken');//oss授权访问

    /*福星降临瓜分番茄豆*/
    Route::get('luckStarPrayInfo', 'v1.Activity.LuckStarController/luckStarPrayInfo');//福星降临瓜分番茄豆

    /*三人夺宝*/
    Route::get('threeTreasures', 'v1.Activity.ThreeLootController/tableInfos');
    Route::get('threeLootInfo', 'v1.Activity.ThreeLootController/tableInfo');
    Route::get('threeLootAsk', 'v1.Activity.ThreeLootController/lootAsk');
    Route::post('grabSeat', 'v1.Activity.ThreeLootController/grabSeat');//抢座位
    /*周星*/
    Route::get('GiftUserList', 'v1.Activity.ActivityStarController/GiftUserList');

    Route::get('getSbImHistory', 'v1.AppDataController/getSbImHistory');   //用户私聊记录
    Route::get('talkBreakIce', 'v1.ImController/talkBreakIce');   //用户私聊破冰语接口


    /*mua*/
    Route::get('getMuaOnlineUserList', 'v1.MemberRecommendController/getMuaOnlineUserList');//MUA在线用户列表
    Route::get('muaNewRoomRecommend', 'v1.LanguageroomController/muaNewRoomRecommendLite');//MUA新厅推荐
    Route::get('muaNewRoomRecommendLite', 'v1.LanguageroomController/muaNewRoomRecommendLite');//MUA新厅推荐
    Route::get('muaRoomKingKong', 'v1.LanguageroomController/muaRoomKingKongLite');//MUA房间金刚位推荐
    Route::get('muaRoomKingKongLite', 'v1.LanguageroomController/muaRoomKingKongLite');//MUA房间金刚位推荐
    Route::get('muaHotRoom', 'v1.LanguageroomController/muaHotRoomLite');//MUA房间列表
    Route::get('muaHotRoomLite', 'v1.LanguageroomController/muaHotRoomLite');//MUA房间列表


    Route::post('tradeUnionAgent', 'v1.WalletController/tradeUnionAgent');//工会代充

    /*回归活动*/
    Route::get('returnActivityInfo', 'v1.Activity.ReturnUserController/returnActivityInfo');   //开年回归活动详情
    Route::post('receiveReturnStar', 'v1.Activity.ReturnUserController/receiveReturnStar');   //开年回归活动领取1
    Route::post('receiveReturnGift', 'v1.Activity.ReturnUserController/receiveReturnGift');   //开年回归活动领取2
    Route::post('receiveReturnCharge', 'v1.Activity.ReturnUserController/receiveReturnCharge');   //开年回归活动领取3

    /*五一甜蜜之旅活动*/
    Route::get('SweetInfo', 'v1.Activity.SweetJourneyController/SweetInfo');   //活动详情
    Route::post('getActivityBox', 'v1.Activity.SweetJourneyController/getActivityBox');   //活动领取宝箱

    #游戏通用购买徽章
    Route::rule('game/buyGoods', 'v1.Box2Controller/buyGoods');
    Route::rule('game/autoBuy', 'v1.Box2Controller/autoBuy');

    Route::rule('box2/init', 'v1.Box2Controller/init');
    Route::rule('box2/info', 'v1.Box2Controller/boxInfo');
    Route::rule('box2/break', 'v1.Box2Controller/breakBox');
    Route::rule('box2/rankList', 'v1.Box2Controller/rankList');
    Route::rule('box2/jinliRankList', 'v1.Box2Controller/jinliRankList');
    Route::rule('box2/buyGoods', 'v1.Box2Controller/buyGoods');

    Route::rule('turntable/init', 'v1.TurntableController/init');
    Route::rule('turntable/turn', 'v1.TurntableController/turnTable');
    Route::rule('turntable/rankList', 'v1.TurntableController/rankList');
    Route::rule('turntable/jinliRankList', 'v1.TurntableController/jinliRankList');

    /*礼物返利*/
    Route::get('giftReturn/init', 'v1.Activity.GiftReturnController/init');
    Route::rule('giftReturn/getReward', 'v1.Activity.GiftReturnController/getReward');

    /*国王专属*/
    Route::get('king/init', 'v1.Activity.KingController/init');
    Route::rule('king/getReward', 'v1.Activity.KingController/getReward');
    Route::rule('king/postAddress', 'v1.Activity.KingController/postAddress');

    /*中秋*/
    Route::get('zhongqiu/init', 'v1.Activity.ZhongQiuController/init');
    Route::rule('zhongqiu/postAddress', 'v1.Activity.ZhongQiuController/postAddress');

    /*中秋pk*/
    Route::rule('zhongqiupk/init', 'v1.Activity.ZhongQiuPKController/init');
    Route::rule('zhongqiupk/checkin', 'v1.Activity.ZhongQiuPKController/checkin');
    Route::rule('zhongqiupk/getCheckInReward', 'v1.Activity.ZhongQiuPKController/getCheckInReward');
    Route::rule('zhongqiupk/addFaction', 'v1.Activity.ZhongQiuPKController/addFaction');

    /*国庆*/
    Route::rule('guoqing/init', 'v1.Activity.GuoQingController/init');
    Route::rule('guoqing/getBoxReward', 'v1.Activity.GuoQingController/getBoxReward');

    /*圣诞*/
    Route::rule('christmas/init', 'v1.Activity.ChristmasController/init');
    Route::rule('christmas/exchange', 'v1.Activity.ChristmasController/doExchange');

    /*跨房活动*/
    Route::rule('acrossPKData', 'v1.Activity.AcrossPKController/acrossPKData');   //设置跨房活动16强

    /*扭蛋*/
    Route::rule('gashapon/init', 'v1.Activity.GashaponController/init');//抽奖初始化
    Route::rule('gashapon/lottery', 'v1.Activity.GashaponController/doLottery');//抽奖
    Route::rule('gashapon/rewardList', 'v1.Activity.GashaponController/rewardList');//抽奖记录
    Route::rule('gashapon/scrolling', 'v1.Activity.GashaponController/scrolling');//抽奖滚屏记录
    Route::rule('gashapon/mallInit', 'v1.Activity.GashaponController/mallInit');//扭蛋机兑换商城初始化
    Route::rule('gashapon/mallExchange', 'v1.Activity.GashaponController/mallExchange');//扭蛋机商城兑换
    Route::rule('gashapon/mallSend', 'v1.Activity.GashaponController/mallSend');//扭蛋机商城赠送
    Route::rule('gashapon/careUserList', 'v1.Activity.GashaponController/careUserList');//关注,粉丝,好友列表
    Route::rule('gashapon/searchFriend', 'v1.Activity.GashaponController/searchFriend');//搜索关注,粉丝,好友列表的好友

    /*520活动*/
    Route::get('LoveWallInfo', 'v1.Activity.ConfessionController/LoveWallInfo');   //活动详情
    Route::get('LoveWallData', 'v1.Activity.ConfessionController/LoveWallData');   //活动详情

    /*七夕活动*/
    Route::get('qixi/init', 'v1.QixiController/init');
    Route::post('qixi/applyList', 'v1.QixiController/applyList');
    Route::post('qixi/appliedList', 'v1.QixiController/appliedList');
    Route::post('qixi/applyCP', 'v1.QixiController/applyCP');
    Route::post('qixi/replyApplyCP', 'v1.QixiController/replyApplyCP');
    Route::post('qixi/applyRemoveCP', 'v1.QixiController/applyRemoveCP');
    Route::post('qixi/replyRemoveCP', 'v1.QixiController/replyRemoveCP');
    Route::post('qixi/openFuDaiGift', 'v1.QixiController/openFuDaiGift');

    //活动流水
    Route::rule('activity/details', 'v1.Activity.GopherController/activityDetails');

    /*安卓华为发包设置*/
    Route::get('enableChat', 'v1.UserInfoController/enableChat');//获取是否可以发送私聊配置
    Route::post('saveEnableChat', 'v1.UserInfoController/saveEnableChat');//更新私聊配置

    //ios,华为 渠道数据分析存储
    Route::post('HuaWeiChannelData', 'v1.AppDataController/HuaWeiChannelData');//华为渠道数据分析存储
    Route::post('AppStoreChannelData', 'v1.AppDataController/AppStoreChannelData');//苹果渠道数据分析存储
    Route::rule('recallSms', 'inner.RecallController/recallSms');   //短信回归活动行为上报 ()

//    后台相关
    Route::rule('memberDetailAudit', 'v1.UserLoginController/memberDetailAudit');   //后台头像/昵称/个性签名/背景墙 审核

    //web任务
    Route::get('webweeksignpop', 'v1.Activity.TaskController/weekSignPop');//周签到弹窗
    Route::post('webweeksign', 'v1.Activity.TaskController/weekSign');//签到
    Route::post('webactivebox', 'v1.Activity.TaskController/activeBox');//活跃度领取
    Route::post('webgettask', 'v1.Activity.TaskController/getTask');//任务领取
    Route::get('webtaskcenter', 'v1.Activity.TaskController/taskCenter');//任务中心
    Route::post('websetshare', 'v1.Activity.TaskController/setshare');//分享回调
    Route::get('webishavetask', 'v1.Activity.TaskController/ishavetask');//是否有任务

    /*万圣节-搞怪*/
    Route::rule('halloween/getMySelfValue', 'v1.Activity.HalloweenController/getMySelfValue');
    Route::rule('halloween/getRichRanking', 'v1.Activity.HalloweenController/getRichRanking');
    Route::rule('halloween/getLikeRanking', 'v1.Activity.HalloweenController/getLikeRanking');



    Route::rule('halloween/halloweenInit', 'v1.Activity.HalloweenController/halloweenInit');  // 初始化页面数据
    Route::rule('halloween/halloweenGetReward', 'v1.Activity.HalloweenController/halloweenGetReward'); // 领取礼物
    Route::rule('halloween/halloweenFire', 'v1.Activity.HalloweenController/halloweenFire');  // 砸大魔王

    //数美回调
    Route::post('audioStreamNotify', 'v1.ShuMeiNotifyController/audioStreamNotify');  //音频流回调

    Route::get('bottomMenuIcon', 'v1.InitDataController/getBottomMenuIcon');   //底部导航栏icon

//    推送上报相关
    Route::rule('chuanglanSmsCallback', 'v1.PushMessageController/chuanglanSmsCallback');   //253短信回送状态上报
    Route::rule('getuiCallback', 'v1.PushMessageController/getuiCallback');   //个推上报

//    手动触发推送
    Route::rule('touchUsers', 'v1.PushMessageController/touchUsers');   //后台手动触发用户
    Route::rule('testCusumer', 'v1.PushMessageController/testCusumer');   //test消息队列处理任务
    Route::rule('testCusumerUserPush', 'v1.PushMessageController/testCusumerUserPush');   //test消息队列处理用户数据推送
//    年票活动相关
    Route::rule('yearTicket/index', 'v1.Activity.YearTicketController/index');  // 年票活动首页
    Route::rule('yearTicket/historyRank', 'v1.Activity.YearTicketController/historyRank');  // 结束后的排名列表
    Route::rule('yearTicket/historyRankSecond', 'v1.Activity.YearTicketController/historyRankSecond');  // 排名列表实时分数


    //春节活动
    Route::get('2022/blessingPool', 'v1.Activity.SpringFestivalController/blessingPool'); //获取奖池累积值
    Route::get('2022/index', 'v1.Activity.SpringFestivalController/index'); //活动页面首页
    Route::post('2022/exchange', 'v1.Activity.SpringFestivalController/exchange'); //活动兑换

    Route::rule('shineHotLook', 'v1.WeShineController/shineHotLook');   //闪萌热门表情(最近使用)
    Route::rule('shineHi', 'v1.WeShineController/shineHi');   //闪萌打招呼表情
    Route::rule('userTalkStatus', 'v1.WeShineController/userTalkStatus');   //和对方的聊天状态
    Route::rule('setHistoryShine', 'v1.WeShineController/setHistoryShine');   //设置历史表情
    Route::rule('getHistoryShine', 'v1.WeShineController/getHistoryShine');   //获取历史表情

    Route::get('gameVoteList', 'v1.Activity.GameVoteController/gameVoteList'); //h5游戏投票活动list
    Route::get('gameVoteFire', 'v1.Activity.GameVoteController/gameVoteFire'); //h5游戏投票活动vote

//    第三方推广上报相关:
    Route::rule('toutiaoReport', 'open.ReportCallbackController/toutiaoReport');  // 今日头条上报
    Route::rule('xingtuReport', 'open.ReportCallbackController/xingtuReport');  // 星图上报
    Route::rule('oppoReport', 'open.ReportCallbackController/oppoReport');  // oppo归因上报
    Route::rule('bizhanReport', 'open.ReportCallbackController/bizhanReport');  // bizhan归因上报

    Route::rule('zhuawawa/zhuawawaFire', 'v1.Activity.ZhuawawaController/zhuawawaFire');  // 抓娃娃 抓
    Route::rule('zhuawawa/zhuawawaIndex', 'v1.Activity.ZhuawawaController/zhuawawaIndex');  // 抓娃娃 首页

    Route::rule('requestEncryptTest', 'test.TestController/requestEncryptTest');  // 加密的测试

    Route::rule('rongtongdaReport', 'open.ReportCallbackController/rongtongdaReport');  // 蓉通达短信上报
})->middleware([\app\middleware\ResponseLog::class,\app\middleware\BaseRawLog::class,\app\middleware\CheckTeen::class]);

Route::group('inner', function () {
    Route::get('queryUserInfoForRoom', 'inner.UserInfoController/userInfoForRoom');//dukeinfo
    Route::get('queryAttentionForRoom', 'inner.UserInfoController/queryAttention');//attention
    Route::get('queryGiftPackInfo', 'inner.UserInfoController/giftPackInfo');//用户礼物背包
    Route::get('queryRoomInfoForRoom', 'inner.RoomController/queryRoomInfo');//roominfo
    Route::get('searchRoomInfoForRoom', 'inner.RoomController/searchRoomInfo');//roominfo
    Route::rule('setUserInfo', 'inner.UserInfoController/perfectUserInfo');   //后台修改用户信息  sync user es  !sync shence
    Route::get('adjustUserAsset', 'inner.GMController/adjustUserAsset'); // 运营调整
    Route::get('getUserAsset', 'inner.GMController/getUserAsset'); // 运营查询用户资产
    Route::rule('virtualPhoneRegister', 'inner.UserInfoController/virtualPhoneRegister'); // 虚拟手机号注册
    Route::get('box2/getRunningPool', 'inner.GMController/getBox2RunningPool'); // 获取宝箱运行奖池
    Route::get('box2/getAllRunningPool', 'inner.GMController/getAllBox2RunningPool'); // 获取宝箱运行奖池
    Route::get('box2/getUser', 'inner.GMController/getBox2User'); // 获取宝箱运行奖池
    Route::get('box2/refreshPool', 'inner.GMController/refreshPool'); // 刷新宝箱运行奖池
    Route::get('box2/refreshAllPool', 'inner.GMController/refreshAllPool'); // 刷新宝箱运行奖池
    Route::get('box2/getBoxBaolv', 'inner.GMController/getBoxBaolv'); // 获取宝箱配置爆率
    Route::post('box2/setConf', 'inner.GMController/setBox2Conf'); // 获取宝箱配置爆率
    Route::get('box2/getRunningBox', 'inner.GMController/getRunningBox'); // 获取宝箱运行奖池
    Route::get('box2/getRunningPool', 'inner.GMController/getBox2RunningPool'); // 获取宝箱运行奖池
    Route::get('turntable/getAllRunningPool', 'inner.GMTurntableController/getAllTurntableRunningPool'); // 获取转盘运行奖池
    Route::get('turntable/getUser', 'inner.GMTurntableController/getTurntableUser'); // 获取转盘运行奖池
    Route::get('turntable/refreshPool', 'inner.GMTurntableController/refreshPool'); // 刷新转盘运行奖池
    Route::get('turntable/refreshAllPool', 'inner.GMTurntableController/refreshAllPool'); // 刷新转盘运行奖池
    Route::get('turntable/getBoxBaolv', 'inner.GMTurntableController/getBoxBaolv'); // 获取转盘配置爆率
    Route::post('turntable/setConf', 'inner.GMTurntableController/setTurntableConf'); // 获取转盘配置爆率
    Route::get('turntable/getRunningBox', 'inner.GMTurntableController/getRunningBox'); // 获取转盘运行奖池
    Route::rule('heartbeat', 'inner.UserInfoController/userOnlineHeartBeat');   //用户在线心跳接口 (userOnlineHeartBeat)
    Route::rule('forum/checkPass', 'inner.GMController/forumCheckPass');   //动态审核通过
    Route::rule('forum/delReply', 'inner.GMController/delForumReply');   //动态评论删除
    Route::rule('setAcrossPKRank', 'inner.GMController/setAcrossPKRank');   //设置跨房活动16强

    Route::rule('game/buyScore', 'inner.GMGameController/buyScore');   //购买积分
    Route::rule('game/getAsset', 'inner.GMGameController/getAsset');   //获得资产数量
    Route::rule('game/addAsset', 'inner.GMGameController/addAsset');   //加一个资产
    Route::rule('game/addAssets', 'inner.GMGameController/addAssets');   //加多个资产
    Route::rule('game/consumeAsset', 'inner.GMGameController/consumeAsset');   //减一个资产
    Route::rule('game/consumeAssets', 'inner.GMGameController/consumeAssets');   //减多个资产
    Route::rule('game/sendGopherKingLed', 'inner.GMGameController/sendGopherKingLed');   //国王地鼠出现跑马灯
    Route::rule('game/sendKOGopherKingLed', 'inner.GMGameController/sendKOGopherKingLed');   //国王地鼠打死跑马灯
    Route::rule('game/sendGopherPublicScreen', 'inner.GMGameController/sendGopherPublicScreen');   //打地鼠公屏
    Route::rule('game/getGiftsInfo', 'inner.GMGameController/getGiftsInfo');   //礼物信息
    Route::rule('game/sendPublicScreen', 'inner.GMGameController/sendPublicScreen');   //通用的2070公屏
    Route::rule('game/sendCommonLedMsg', 'inner.GMGameController/sendCommonLedMsg');   //通用的转盘类的跑马灯
    Route::rule('game/sendAssistantMsg', 'inner.GMGameController/sendAssistantMsg');   //发送小秘书消息
    Route::rule('blockUserNotice', 'inner.UserInfoController/blockUserNotice');   //后台封号操作通知

    Route::get('setAudioCheckSwitch', 'inner.RoomNewsController/audioStreamCheckSwitch');   //后台音频流检测开关
    Route::rule('touchUsers', 'v1.PushMessageController/touchUsers');   //后台手动触发用户
    Route::rule('shumeiCheck', 'v1.PushMessageController/shumeiCheck');   //数美验证文本内容

    Route::rule('gashapon/setConf', 'inner.GMGashaponController/setConf'); // 设置扭蛋机配置
    Route::rule('gashapon/getRunningPool', 'inner.GMGashaponController/getRunningPool'); // 获取运行奖池
    Route::rule('gashapon/refreshPool', 'inner.GMGashaponController/refreshPool'); // 刷新运行奖池

    Route::post('collectFee', 'inner.HyperfController/collectFee');         //扣钱
    Route::post('deliveryGifts', 'inner.HyperfController/deliveryGifts');   //发礼物
    Route::post('newerTask', 'inner.HyperfController/newerTask');           //新人任务
    Route::post('gerUserBubble', 'inner.HyperfController/gerUserBubble');   //获取用户气泡框

    Route::rule('complaintUserFollow', 'inner.UserInfoController/complaintUserFollow');    //举报用户(新版本)
    Route::rule('complaintUserChange', 'inner.UserInfoController/complaintUserChange');    //修改举报状态(新版本)

    Route::rule('homeHotRoomList', 'inner.RoomController/homeHotRoomList');   //获取首页推荐房间
    Route::rule('recreationHotRoomList', 'inner.RoomController/recreationHotRoomList');   //获取娱乐页推荐房间

    Route::rule('withdraw/addAsset', 'inner.WithdrawController/addAsset');   //公众号提现 增加资产
    Route::rule('withdraw/consumeAsset', 'inner.WithdrawController/consumeAsset');   //公众号提现 扣减资产
    Route::rule('roomInfoAudit', 'inner.RoomController/roomInfoAudit');   // 房间信息审核

    //    后台接口相关:
    Route::rule("room/editRoom", "inner.RoomController/editRoom");   //后台修改房间信息  sync room es  --RoomUpdateEvent
    Route::rule("room/roomOssFile", "inner.RoomController/roomOssFile");   //上传背景墙  sync room es  --RoomUpdateEvent
    Route::rule("room/addRoomPretty", "inner.RoomController/addRoomPretty");   //添加房间靓号 sync room es  --RoomUpdateEvent
    Route::rule("room/addRoomParty", "inner.RoomController/addRoomParty");   //加入公会房间 sync room es  --InnerRoomPartyEvent
    Route::rule("room/roomInfoUpdate", "inner.RoomController/roomInfoUpdate");   //更新房间拓展信息 sync room es --RoomUpdateEvent
    Route::rule("room/addGuidRoomIndex", "inner.RoomController/addGuidRoomIndex");   //指定首页公会房间 sync room es --RoomUpdateEvent
    Route::rule("room/delGuidRoomIndex", "inner.RoomController/delGuidRoomIndex");   //删除指定首页公会房间 sync room es --RoomUpdateEvent
    Route::rule("room/addGuidRoom", "inner.RoomController/addGuidRoom");   //指定首页公会房间 sync room es --RoomUpdateEvent
    Route::rule("room/delGuidRoom", "inner.RoomController/delGuidRoom");   //删除公会房间 sync room es --RoomUpdateEvent

    Route::post("checkForum", "inner.ForumController/checkForum");   //动态审核
    Route::post("delForum", "inner.ForumController/delForum");   //删除动态审核
    Route::post("createGuild", "inner.GuildController/createGuild");   //创建公会
    Route::post("editGuildInfo", "inner.GuildController/editGuildInfo");   //修改公会信息
    Route::post("editGuildMember", "inner.GuildController/editGuildMember");   //修改公会成员信息 sync user es --InnerAuditMemberEvent
    Route::post("addGuildMember", "inner.GuildController/addGuildMember");   //添加公会成员 sync user es --InnerAuditMemberEvent
    Route::post("removeGuildMember", "inner.GuildController/removeGuildMember");   //移除公会成员 sync user es  --InnerAuditMemberEvent

    Route::rule("user/addYsUser", "inner.UserInfoController/addYsUser");   //添加隐身用户
    Route::rule("user/delYsUser", "inner.UserInfoController/delYsUser");   //删除隐身用户
    Route::rule("user/addUser", "inner.UserInfoController/addUser");   //添加虚拟用户  sync user es  --UserRegisterEvent
    Route::rule("user/updateUserInvitcode", "inner.UserInfoController/updateUserInvitcode");   //修改用户邀请码  sync user es  --UserUpdateProfileEvent
    Route::rule("user/dukeMemberAdd", "inner.UserInfoController/dukeMemberAdd");   //用户爵位调整  sync user es  --DukeLevelChangeEvent
    Route::rule("user/resetAttention", "inner.UserInfoController/resetAttention");   //重置用户认证状态  sync user es  --DukeLevelChangeEvent

//    公会后台相关
    Route::rule("gh/exitMember", "inner.GhMemberController/exitMember");   //工会成员审核通过  sync user es  --GhAuditMemberEvent  !sync shence
    Route::rule("gh/kickMember", "inner.GhMemberController/kickMember");   //踢出公会成员 sync user es  --GhAuditMemberEvent  !sync shence
    Route::rule("gh/agreeApply", "inner.GhMemberController/agreeApply");   //同意申请退出 sync user es  --GhAuditMemberEvent  !sync shence
    Route::rule("gh/refuseApply", "inner.GhMemberController/refuseApply");   //拒绝申请退出工会
    
})->middleware([\app\middleware\ResponseLog::class]);

/**
 * 对外开放接口
 */
Route::group('open', function () {
    Route::rule('saveClickInfo', 'open.AdvertDeliveryController/saveClickInfo');
    Route::rule('uniqueIdfa', 'open.AdvertDeliveryController/uniqueIdfa');
    Route::rule('uniqueIdfa', 'open.AdvertDeliveryController/uniqueIdfa');

//    第三方推广上报相关:
    Route::rule('kuaishouReport', 'open.ReportCallbackController/kuaishouReport');  // 快手上报

    Route::get('ccUserCheckLogin','open.ChaChaLoginCheckController/checkUser'); //茶茶用户限制登录
})->middleware([\app\middleware\ResponseLog::class]);



Route::group('test', function () {
//    Route::rule('setAcrossPKRank', 'inner.GMController/setAcrossPKRank');   //设置跨房活动16强
//    Route::get('publishBaolvTask','test.TestBox2Controller/publishBaolvTask');
//    Route::get('baolvTaskInfo','test.TestBox2Controller/baolvTaskInfo');
//    Route::get('box2/getRunningPool','test.TestBox2Controller/getBox2RunningPool'); // 获取宝箱运行奖池
//    Route::get('box2/getAllRunningPool','test.TestBox2Controller/getAllBox2RunningPool'); // 获取宝箱运行奖池
//    Route::get('box2/getUser','test.TestBox2Controller/getBox2User'); // 获取宝箱运行奖池
//    Route::get('box2/clearUser','test.TestBox2Controller/clearBox2User'); // 获取宝箱运行奖池
//    Route::get('box2/refreshPool','test.TestBox2Controller/refreshPool'); // 刷新宝箱运行奖池
//    Route::get('box2/refreshAllPool','test.TestBox2Controller/refreshAllPool'); // 刷新宝箱运行奖池
//    Route::get('box2/getBoxBaolv','test.TestBox2Controller/getBoxBaolv'); // 获取宝箱配置爆率
//    Route::get('updatePropTime', 'test.TestController/updatePropTime');// 头像过期时间修改

//    Route::get('turntable/publishBaolvTask','test.TestTurntableController/publishBaolvTask');
//    Route::get('turntable/baolvTaskInfo','test.TestTurntableController/baolvTaskInfo');
//    Route::get('turntable/getRunningPool','test.TestTurntableController/getBox2RunningPool'); // 获取宝箱运行奖池
//    Route::get('turntable/getAllRunningPool','test.TestTurntableController/getAllBox2RunningPool'); // 获取宝箱运行奖池
//    Route::get('turntable/getUser','test.TestTurntableController/getBox2User'); // 获取宝箱运行奖池
//    Route::get('turntable/clearUser','test.TestTurntableController/clearBox2User'); // 获取宝箱运行奖池
//    Route::get('turntable/refreshPool','test.TestTurntableController/refreshPool'); // 刷新宝箱运行奖池
//    Route::get('turntable/refreshAllPool','test.TestTurntableController/refreshAllPool'); // 刷新宝箱运行奖池
//    Route::get('turntable/getBoxBaolv','test.TestTurntableController/getBoxBaolv'); // 获取宝箱配置爆率

//    Route::get('testKwFilter','test.TestController/testKwfilter'); // 测试接口
//    Route::get('test', 'test.TestController/test'); // 测试接口
//    Route::get('AuthSign', 'test.TestController/AuthSign'); // 测试接口
//    Route::post('encryptDecode', 'test.TestController/encryptDecode'); // 测试接口 decode
//    Route::post('encryptEncode', 'test.TestController/encryptEncode'); // 测试接口 encode
//    Route::get('testSendGift','test.TestController/testSendGift'); // 测试接口
//    Route::get('testSetDuke','test.TestController/testSetDuke'); // 测试接口
//    Route::get('testGetDuke','test.TestController/testGetDuke'); // 测试接口
//    Route::get('testSetVip','test.TestController/testSetVip'); // 测试接口
//    Route::get('testSearchRoom','test.TestController/testSearchRoom'); // 测试接口
//    Route::get('testSearchUser','test.TestController/testSearchUser'); // 测试接口
//    Route::get('testConf','test.TestController/testConf'); // 测试接口
//    Route::get('testgame', 'test.TestController/testGame'); // 测试接口
//    Route::get('testgames', 'test.TestController/testGameResult'); // 测试接口
//    Route::get('testAes','test.UnitTestController/index'); // 测试接口
//    Route::get('turntableInit', 'v1.TurntableController/init');
//    Route::rule('turntableTurn', 'v1.TurntableController/turnTable');
//    Route::get('testQueue','test.TestQueueController/index');//test queue
//    Route::get('updatePropTime', 'test.TestController/updatePropTime');// test
//    Route::get('giftReturn/setUser', 'test.TestController/giftReturnSetUser');
//    Route::get('giftReturn/clearUser', 'test.TestController/giftReturnClearUser');

//    Route::rule('decodeAes', 'test.TestAesController/decodeAes'); // testaes
//    Route::rule('chuanglansms', 'test.TestChuanglanController/chuanglansms'); // testaes
//    Route::rule('testYunxin', 'test.TestController/testYunxin'); // testaes
//    Route::rule('halloweenBean', 'test.TestController/halloweenBean'); // 万圣节活动付款
//    Route::rule('halloweenAddCandy', 'test.TestController/halloweenAddCandy'); // 万圣节活动加糖果
//    Route::rule('halloweenPoolDetail', 'test.TestController/halloweenPoolDetail'); // 万圣节活动爆率查询
//    Route::rule('checkImg', 'test.TestController/checkImg'); // 图片检测
//    Route::rule('gashapon/getRunningPool', 'inner.GMGashaponController/getRunningPool'); // 获取运行奖池
//    Route::rule('gashapon/refreshPool', 'inner.GMGashaponController/refreshPool'); // 刷新运行奖池

//    Route::rule('loadKuaishouReport', 'open.ReportCallbackController/loadKuaishouReport');  // 快手上报
//    Route::rule('loadToutiaoReport', 'open.ReportCallbackController/loadToutiaoReport');  // 头条上报注册
//    Route::rule('loadToutiaoActive', 'open.ReportCallbackController/loadToutiaoActive');  // 头条上报激活
//    Route::rule('loadToutiaoPay', 'open.ReportCallbackController/loadToutiaoPay');  // 头条上报付费

//    Route::rule('zhuawawa/addCoin', 'v1.Activity.ZhuawawaController/addCoin');  // 抓娃娃 抓

})->middleware([\app\middleware\ResponseLog::class,\app\middleware\BaseRawLog::class]);
/*版本审核中 数据进行处理*/
Route::group('v1', function () {
    Route::get('getOnlineUserList', 'v1.MemberRecommendController/onlineUser');//在线用户列表新版
    Route::rule('indexHotRoom', 'v1.LanguageroomController/indexHotRoom');  //首页 热门房间推荐位
    Route::get('newRoomList', 'v1.LanguageroomController/newRoomListLite'); //首页房间列表接口新版
    Route::get('indexListType', 'v1.MemberRecommendController/indexListType'); //在线列表分类
    Route::get('newRoomTypeList', 'v1.LanguageroomController/newRoomTypeList'); //新版热门房间
    Route::get('partyRoomList', 'v1.LanguageroomController/partyRoomListLite'); //房间派对列表
    Route::get('searchlog', 'v1.SearchController/searchlog');//搜索推荐房间
    Route::get('searchlogLite', 'v1.SearchController/searchlogLite');//搜索推荐房间lite
    Route::get('search', 'v1.SearchController/search'); //搜索用户
    Route::get('searchLite', 'v1.SearchController/searchLite'); //搜索lite
    Route::get('searchProcess', 'v1.SearchController/searchProcess'); //搜索过程中
    Route::rule('partRecommend', 'v1.LanguageroomController/partRecommend');  // 派对页人气推荐
    Route::get('roomdetails', 'v1.LanguageroomController/RoomDetails');  //房间内活动
    Route::get('recommendUser', 'v1.MemberRecommendController/recommendUser');//cp匹配
    Route::rule('randRoom', 'v1.LanguageroomController/randRoom'); //模拟恋爱
    Route::get('chartsList', 'v1.RankListController/getList'); //排行榜
    Route::post('bannerList', 'v1.BannerController/bannerList');//bannerList
    Route::rule('indexIcon', 'v1.RankListController/indexIcon');  // 首页榜单小标icon

})->middleware([\app\middleware\ResponseLog::class,\app\middleware\BaseRawLog::class,\app\middleware\VersionCheck::class]);


Route::group('v1', function () {
    Route::rule('careUser', 'v1.AttentionController/careUser');               //关注用户
})->middleware([\app\middleware\VersionCheck::class, \app\middleware\ResponseLog::class,\app\middleware\BaseRawLog::class]);


Route::group('test', function () {
    Route::rule('encryptTest', 'test.TestController/encryptTest');  // 加密的测试
    Route::rule('requestEncryptTest', 'test.TestController/requestEncryptTest');  // 加密的测试
})->middleware([\app\middleware\VersionCheck::class, \app\middleware\ResponseLog::class,\app\middleware\BaseAesLog::class]);


Route::group('test', function () {
    Route::rule('testParam', 'test.TestController/testParam');  // 加密的测试
})->middleware([\app\middleware\VersionCheck::class, \app\middleware\ResponseLog::class,\app\middleware\BaseRawLog::class]);


Route::group('v1', function () {
    Route::rule('shineSearch', 'v1.WeShineController/shineSearch');   //闪萌搜索接口
})->middleware([\app\middleware\LimitFlow::class, \app\middleware\ResponseLog::class, \app\middleware\BaseRawLog::class]);
