<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        //定时任务
//    	'admincommand' => 'app\admin\script\AdminCommand',
        'sendmsg_test' => 'app\api\script\ForumReplyMsgCommand',    //回复帖子
        'logintime_test' => 'app\api\script\LoginTimeCommand',        //用户登录时间
        'fangjian_test' => 'app\web\script\FangjianCommand',        //每天房间前三名统计分数
        'shengyou_test' => 'app\web\script\ShengyouCommand',        //声优晋级

        'dt_test' => 'app\api\script\ForumCheckCommand',        //动态
        'dtv_test' => 'app\api\script\ForumVoiceCommand',        //动态yuyin
        'visitmsg_test' => 'app\api\script\VisitorMsgCommand',        //访客消息
        'PropSetCommand' => 'app\api\script\PropSetCommand',        //自动卸载道具
        'history_test' => 'app\api\script\HistoryUserCommand',              //访客记录表
        'setv_test' => 'app\api\script\SetvisCommand',              //更新到缓存
        'useryunxin_test' => 'app\api\script\UserYunXinCommand',    //云信注册
        'eggcoin_test' => 'app\api\script\EggcoinCommand',
        'Eggrandomy_test' => 'app\api\script\EggrandomyCommand',
        'Eggrandomj_test' => 'app\api\script\EggrandomjCommand',
        'LoveCommand' => 'app\web\script\LoveCommand',                //活动脚本
        'RoomBlackCommand' => 'app\api\script\RoomBlackCommand',                          //房间禁言与踢出脚本
        'RoomErCommand' => 'app\api\script\RoomErCommand',                          //房间房主加入管理员脚本
        'RoomUserLineCommand' => 'app\api\script\RoomUserLineCommand',                          //房间首页推荐脚本
        'GiftReduCommand' => 'app\api\script\GiftReduCommand',                          //房间礼物热度值脚本
        'RoomReduCommand' => 'app\api\script\RoomReduCommand',                          //房间内热度值脚本
        'IMHistoryCommand' => 'app\api\script\IMHistoryCommand',
        'UninstallVipCommand' => 'app\api\script\UninstallVipCommand',
        'delredisCommand' => 'app\api\script\DelredisCommand',
        'fuxingCommand' => 'app\api\script\fuxingCommand',
        'UserReCallOneCommand' => 'app\api\script\UserReCallOneCommand',                          //房间首页推荐脚本
        'UserReCallTwoCommand' => 'app\api\script\UserReCallTwoCommand',                          //房间首页推荐脚本
        'UserReCallFiveFreeCommand' => 'app\api\script\UserReCallFiveFreeCommand',                          //房间首页推荐脚本
        'UserReCallFivePayCommand' => 'app\api\script\UserReCallFivePayCommand',                          //房间首页推荐脚本
        'UserReCallTenFreeCommand' => 'app\api\script\UserReCallTenFreeCommand',                          //房间首页推荐脚本
        'UserReCallTenPayCommand' => 'app\api\script\UserReCallTenPayCommand',                          //房间首页推荐脚本
        'UserReCallFifteenFreeCommand' => 'app\api\script\UserReCallFifteenFreeCommand',                          //房间首页推荐脚本
        'UserReCallFifteenPayCommand' => 'app\api\script\UserReCallFifteenPayCommand',                          //房间首页推荐脚本
        'UserReCallTwentyFreeCommand' => 'app\api\script\UserReCallTwentyFreeCommand',                          //房间首页推荐脚本
        'UserReCallTwentyPayCommand' => 'app\api\script\UserReCallTwentyPayCommand',                          //房间首页推荐脚本
        'UserReCallTwentyFiveFreeCommand' => 'app\api\script\UserReCallTwentyFiveFreeCommand',                          //房间首页推荐脚本
        'UserReCallThirtyFreeCommand' => 'app\api\script\UserReCallThirtyFreeCommand',                          //房间首页推荐脚本
        'UserReCallThirtyPayCommand' => 'app\api\script\UserReCallThirtyPayCommand',                          //房间首页推荐脚本
        'FixDataCommand' => 'app\api\script\FixDataCommand',                          //房间首页推荐脚本
        'MoveAssetDataCommand' => 'app\api\script\MoveAssetDataCommand',                          //房间首页推荐脚本


        'DukeCartoonCommand' => 'app\api\script\DukeCartoonCommand',                          //爵位升级提示
        'DukeNoticeCommand' => 'app\api\script\DukeNoticeCommand',                          //爵位即将过期提醒
        'DukeLevelCommand' => 'app\api\script\DukeLevelCommand',                          //爵位降级保级脚本
        'NewYearPartitionCommand' => 'app\api\script\NewYearPartitionCommand',                          //爵位降级保级脚本
        'SetGiftStartCommand' => 'app\api\script\SetGiftStartCommand',            //周星月星榜单
        'EventYearPartitionCommand' => 'app\api\script\EventYearPartitionCommand',            //年度盛典产出参与决赛房间及用户脚本
        'UpdatePropStatusCommand' => 'app\api\script\UpdatePropStatusCommand',            //年度盛典产出参与决赛房间及用户脚本
        'LuckStarPartitionCommand' => 'app\api\script\LuckStarPartitionCommand',                          //福星降临瓜分番茄豆脚本
        'UserOnlineKickCommand' => 'app\api\script\UserOnlineKickCommand',                          //用户在线列表踢出脚本
        'ValentinesDayPartitionCommand' => 'app\api\script\ValentinesDayPartitionCommand',                          //情人节瓜分番茄豆
        'ThreeLootRefundCommand' => 'app\api\script\ThreeLootRefundCommand',                          //三人夺宝退款脚本
        'ThreeLootGetPoolTypeCommand' => 'app\api\script\ThreeLootGetPoolTypeCommand',                          //三人夺宝获取桌子状态
        'SyncUserGiftCommand' => 'app\api\script\SyncUserGiftCommand',      //同步用户礼物脚本
        'TestCommand' => 'app\api\script\TestCommand',
        'LoadConfForDB' => 'app\command\LoadConfForDB',
        'UserBucketCommand' => 'app\api\script\UserBucketCommand',                         //在线用户数据清洗处理
        'SubTaskCommand' => 'app\api\script\SubTaskCommand',     //subtestdemo
        'QueuetaskCommand' => 'app\api\script\queue\QueuetaskCommand',    //QueuetaskCommond Command
        'GuildRoomCommand' => 'app\api\script\GuildRoomCommand',                         //公会房间数据清洗更新
        'DeductRoomHotCommand' => 'app\api\script\DeductRoomHotCommand',       //公会房间热度值扣减
        'PersonRoomCommand' => 'app\api\script\PersonRoomCommand',         //个人房间数据清洗更新
        'MuaRoomCommand' => 'app\api\script\MuaRoomCommand',         //mua房间数据清洗更新
        'MuaRoomKingKongCommand' => 'app\api\script\MuaRoomKingKongCommand',         //mua房间金刚位数据清洗更新
        'MuaRoomDataRefreshCommand' => 'app\api\script\MuaRoomDataRefreshCommand',         //mua推荐位房间数据cache刷新
        'QueueCommand' => 'app\api\script\QueueCommand',    //queue task
        'Consumer' => 'app\api\job\Consumer',    //queue queue demo
        'ShortQueueCommand' => 'app\api\script\ShortQueueCommand',   //queue ShortQueueCommand
        'GenerateNicknameCommand' => 'app\api\script\GenerateNicknameCommand',   //queue GenerateNicknameCommand
        'ExportNicknameCommand' => 'app\api\script\ExportNicknameCommand',   //queue ShortQueueCommand
        'UserCancellationCheckCommand' => 'app\api\script\UserCancellationCheckCommand', //注销用户15天过滤
        'DealSubTableDataCommand' => 'app\api\script\DealSubTableDataCommand', //注销用户15天过滤
        'ZhongQiuPKCommand' => 'app\api\script\ZhongQiuPKCommand',
        'HalloweenClearCommon' => 'app\api\script\HalloweenClearCommon', //万圣节活动物料回收脚本
        'GuoQingCommand' => 'app\api\script\GuoQingCommand',
        'RecallSmsCommand' => 'app\api\script\RecallSmsCommand',//用户召回短信活动
        'ClearGuildMemberCommand' => 'app\api\script\ClearGuildMemberCommand', //清理工会 超过15天没有登录的成员
        'RecallQueueCommand' => 'app\api\script\RecallQueueCommand',//用户召回（长期活动队列）
        'CreateUserAssetLogCommand' => 'app\api\script\CreateUserAssetLogCommand',    //生成金流表的分表任务
        'AutoQuitGuildCommand' => 'app\api\script\AutoQuitGuildCommand', //申请退出公会 15日管理员未处理 自动退出
        'UserProfileShuMeiCheckCommand' => 'app\api\script\UserProfileShuMeiCheckCommand', //留存用户信息数美检测
        'RoomProfileShuMeiCheckCommand' => 'app\api\script\RoomProfileShuMeiCheckCommand', //留存房间信息数美检测
        'RecommendRoomCommand' => 'app\api\script\RecommendRoomCommand',                         //房间推荐位相关
        'BlessingPoolPartitionCommand' => 'app\api\script\BlessingPoolPartitionCommand',         //定时瓜分
        'ImMessagePushEsCommand' => 'app\api\script\ImMessagePushEsCommand', // mysql push to es
        'ConfessionLovePartitionCommand' => 'app\api\script\ConfessionLovePartitionCommand', // mysql push to es
        'VipAutoPayCommand' => 'app\api\script\VipAutoPayCommand', // 支付宝vip自动续费 扣款
        'UserSpecialCareCommand' => 'app\api\script\UserSpecialCareCommand', // 用户特别关心推送
        'VipIsPayTempCommand' => 'app\api\script\VipIsPayTempCommand', // 获取之前充值过的vip
        'UserRegisterSensorsCommand' => 'app\api\script\UserRegisterSensorsCommand', //神策用户表脚本

        'ElasticQueueCommand' => 'app\api\script\ElasticQueueCommand',//es数据同步房间相关
        'UserDatabaseCreateDatabaseCommand' => 'app\api\shardingScript\UserDatabaseCreateDatabaseCommand',
        'UserDatabaseCreateTableCommand' => 'app\api\shardingScript\UserDatabaseCreateTableCommand',
        'ExportZbMemberCommand' => 'app\api\shardingScript\ExportZbMemberCommand',
        'ExportZbAttentionCommand' => 'app\api\shardingScript\ExportZbAttentionCommand',
        'ExportZbRoomCommand' => 'app\api\shardingScript\ExportZbRoomCommand',
        'RoomModelDaoCommand' => 'app\api\shardingScript\RoomModelDaoCommand',
        'DiffZbMemberCommand' => 'app\api\shardingScript\DiffZbMemberCommand',
        'GeneralPushEsTempCommand' => 'app\api\shardingScript\GeneralPushEsTempCommand', // 通用 mysql push to es
        'VipHandleExpireCommand' => 'app\api\script\VipHandleExpireCommand', // 处理vip过期
        'VersionArraignmentCommand' => 'app\api\script\VersionArraignmentCommand', // 处理提审数据

        //sharding2
        'BiDelOtherTableCommand' => 'app\api\shardingScript2\bi\DelOtherTableCommand', //bi删除不需要的表
        'CommonDelOtherTableCommand' => 'app\api\shardingScript2\common\DelOtherTableCommand', //common删除不需要的表
        'MakeUserIDsCommand' => 'app\api\script\MakeUserIDsCommand', //生成新id
        'FixExportZbMemberCommand' => 'app\api\script\FixExportZbMemberCommand', //生成新id
        'TestSendIMCommand' => 'app\api\script\TestSendIMCommand', //测试im消息
        'InitializeCommand' => 'app\api\script\InitializeCommand',    //数据初始化命令行
        'AutoSubscriptionCompensateCommand' => 'app\api\script\AutoSubscriptionCompensateCommand', // 自动续费补偿机制
        'UserMigrateCommand' => 'app\api\script\UserMigrateCommand', // 用户迁移

        'HalloweenCommand' => 'app\api\script\HalloweenCommand',
        'FixRankDataCommand' => 'app\api\script\FixRankDataCommand',
    ],

];

