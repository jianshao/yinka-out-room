CREATE TABLE `zb_member_privilege`
(
    `id`      int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '会员特权列表id',
    `type`    TINYINT(1) NOT NULL COMMENT '会员特权标识 0：会员 1：年费会员',
    `picture` varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '会员中心图片',
    `content` varchar(60)                     NOT NULL DEFAULT '' COMMENT '会员中心图片文案',
    `sort`    int(3) NOT NULL DEFAULT 0 COMMENT '特权排序',
    `status`  int(2) NOT NULL DEFAULT '0' COMMENT '0 禁用 1 启用',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='会员特权信息';

alter table zb_member modify column `is_vip` int (1) DEFAULT '0' COMMENT '0 非会员 1会员  2年费会员';
alter table zb_gift
    ADD COLUMN `is_vip` tinyint(1) NOT NULL DEFAULT 0  COMMENT '礼物是否为vip礼物：0否 1是';


CREATE TABLE `zb_vip_chargedetail`
(
    `id`         int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid`        int(11) UNSIGNED NOT NULL DEFAULT 0,
    `rmb`        int(11) NOT NULL COMMENT '充值人民币金额以分为单位',
    `status`     TINYINT(1) NOT NULL DEFAULT 0 COMMENT '订单状态（0未支付，1已支付）',
    `createTime` datetime     NOT NULL COMMENT '订单创建时间',
    `orderNo`    varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '唯一订单号',
    `dealid`     varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信或支付宝订单号',
    `platform`   int(2) UNSIGNED NULL DEFAULT 0 COMMENT '支付平台：0（苹果支付），1（微信），2（支付宝）',
    `type`       tinyint(2) NOT NULL DEFAULT 1 COMMENT '充值类型 1：1个月  3:3个月  6：6个月 12：12个月',
    `is_active`  int(11) NOT NULL DEFAULT 0 COMMENT '状态 1续费vip 2激活vip',
    `outparam`   varchar(255) NOT NULL DEFAULT '' COMMENT '微信支付宝回调信息',
    PRIMARY KEY (`id`),
    INDEX        `join` (`uid`) USING BTREE
) ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
COMMENT='会员充值订单表';


alter table `zb_member`
    ADD COLUMN `vip_exp` int(11) NOT NULL DEFAULT 0  COMMENT '用户普通会员到期时间';
alter table `zb_member`
    ADD COLUMN `svip_exp` int(11) NOT NULL DEFAULT 0  COMMENT '用户年费会员到期时间';



ALTER TABLE `zb_coindetail` MODIFY COLUMN `action` CHAR (32) CHARACTER
    SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'sendgift 赠送礼物, cash 钻石提现,
 changes 钻石转番茄豆,guard 守护 vip购买vip ,BuyHarmmer买锤子，BreakEgg消耗锤子，BreakEggGetGift砸蛋获得礼物，
 sendgiftFromBag消费砸蛋礼物,BuyHarmmer1金槌子，FingerGameStart发起猜拳，FingerGameFight猜拳应战，FingerGameSettlement猜拳结算,
 attire装扮,vipCharge 会员充值';

alter table `zb_gift`
    ADD COLUMN `is_vip` tinyint(1) NOT NULL DEFAULT 0  COMMENT '礼物是否为vip礼物 0否 1:是';
alter table `zb_gift`
    ADD COLUMN `gift_gold` int(11) NOT NULL DEFAULT 0  COMMENT '金币价值' after gift_coin;


CREATE TABLE `zb_firstpay_hammers`
(
    `id`         int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '',
    `uid`        int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
    `type`       TINYINT(1) NOT NULL DEFAULT 0 COMMENT '类型 0：银宝箱 1：金宝箱',
    `status`     TINYINT(1) NOT NULL DEFAULT 1 COMMENT '钥匙状态 0：已使用 1：未使用',
    `createTime` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updateTime` int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='用户首充赠送银钥匙详情表';


ALTER TABLE `zb_gift` MODIFY COLUMN `gift_type` int (11) NOT NULL DEFAULT '1' COMMENT '礼物类型1.普通礼物 2动画礼物 3免费礼物 4终极礼物 5极品礼物';



#1
.
4.0新增sql
ALTER TABLE `zb_gift` MODIFY COLUMN `gift_type` int (11) NOT NULL DEFAULT '1' COMMENT '礼物类型1.普通礼物 2动画礼物 3免费礼物 4终极礼物 5极品礼物';
ALTER TABLE `zb_attire`
    ADD COLUMN `attire_move_image` varchar(255) NOT NULL DEFAULT '' COMMENT '装扮动态图片' after attire_image;



#1
.
5.0会员开发
CREATE TABLE `zb_member_privilege`
(
    `id`              int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '会员特权列表id',
    `type`            tinyint(1) NOT NULL COMMENT '会员特权标识 1：会员 2：年费会员',
    `picture`         varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '会员中心图片',
    `title`           varchar(60) COLLATE utf8_bin    NOT NULL DEFAULT '' COMMENT '会员中心图片文案',
    `content`         varchar(200) COLLATE utf8_bin   NOT NULL COMMENT '会员中心预览文案',
    `preview_picture` varchar(100) COLLATE utf8_bin   NOT NULL COMMENT '会员中心预览图片',
    `sort`            int(3) NOT NULL DEFAULT '0' COMMENT '特权排序',
    `status`          int(2) NOT NULL DEFAULT '0' COMMENT '0 禁用 1 启用',
    `state`           int(2) NOT NULL DEFAULT '1' COMMENT '1亮色0暗色',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='会员特权信息';

CREATE TABLE `zb_vip_chargedetail`
(
    `id`         int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uid`        int(11) UNSIGNED NOT NULL DEFAULT 0,
    `rmb`        int(11) NOT NULL COMMENT '充值人民币金额以分为单位',
    `status`     TINYINT(1) NOT NULL DEFAULT 0 COMMENT '订单状态（0未支付，1已支付）',
    `createTime` datetime     NOT NULL COMMENT '订单创建时间',
    `orderNo`    varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '唯一订单号',
    `dealid`     varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '微信或支付宝订单号',
    `platform`   int(2) UNSIGNED NULL DEFAULT 0 COMMENT '支付平台：0（苹果支付），1（微信），2（支付宝）',
    `type`       tinyint(2) NOT NULL DEFAULT 1 COMMENT '充值类型 1：1个月  3:3个月  6：6个月 12：12个月',
    `is_active`  int(11) NOT NULL DEFAULT 0 COMMENT '状态 1续费vip 2激活vip',
    `outparam`   varchar(255) NOT NULL DEFAULT '' COMMENT '微信支付宝回调信息',
    PRIMARY KEY (`id`),
    INDEX        `join` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='会员充值订单表';

alter table zb_member modify column `is_vip` int (1) DEFAULT '0' COMMENT '0 非会员 1vip  2svip';

CREATE TABLE `zb_im_message`
(
    `id`          int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `fromUid`     int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '发送者id',
    `toUid`       int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '接收者id',
    `textContent` text DEFAULT '' COMMENT '文字消息内容',
    `image`       text DEFAULT '' COMMENT '图片消息',
    `createTime`  int(11) DEFAULT 0 COMMENT '私聊创建时间',
    PRIMARY KEY (`id`),
    INDEX         `fromUid` (`fromUid`) USING BTREE,
    INDEX         `createTime` (`createTime`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='IM消息';


ALTER TABLE `zb_photo_wall`
    ADD COLUMN `is_vip` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是vip背景图' after image;
ALTER TABLE `zb_emoticon`
    ADD COLUMN `is_vip` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是vip表情' after is_lock;
ALTER TABLE `zb_attire`
    ADD COLUMN `is_vip` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是vip装扮 0否 1vip 2svip' after is_show;
ALTER TABLE `zb_emoticon`
    ADD COLUMN `mold` tinyint(1) NOT NULL DEFAULT 1 COMMENT '表情分类 1：普通表情 2：vip表情 3---' after is_lock;
ALTER TABLE `zb_emoticon`
    ADD COLUMN `mold_icon` varchar(200) NOT NULL DEFAULT '' COMMENT '表情分类图标缩略图' after mold;

ALTER TABLE `zb_siteconfig`
    ADD COLUMN `vip_charge` text NOT NULL COMMENT '会员充值配置' after red_packets;

INSERT INTO `zb_gift`(`type`, `gift_name`, `gift_number`, `gift_coin`, `gift_gold`, `gift_image`, `gift_animation`,
                      `gift_type`, `bigtypes`, `creat_time`, `animation`, `class_type`, `broadcast`, `status`,
                      `one_weight`, `is_sort`, `prize_rate`, `color_weight`, `prop_info`, `is_show`, `is_vip`)
VALUES (0, '水晶球', 200, 200, 0, '/gift/20200810/527f4b6fced60f82ee27b4cd4d8876e8.png',
        '/gift/20200810/379101e683b64627900d2f67969e9596.svga', 1, 1, '0000-00-00 00:00:00',
        '/gift/20200810/9340151b943e554a0e971459fe976e89.gif', 1, 2, 1, '0', 0, '', 0, '', 1, 1);



#待更新
DROP TABLE zb_user_black;
CREATE TABLE `zb_user_black`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `user_id`     int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `register_ip` varchar(40) NOT NULL DEFAULT '' COMMENT '注册ip',
    `login_ip`    varchar(40) NOT NULL DEFAULT '' COMMENT '登陆ip',
    `device_id`   varchar(40) NOT NULL DEFAULT '' COMMENT '设备号',
    `ctime`       int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `status`      tinyint(1) NOT NULL DEFAULT '1' COMMENT '记录状态 0:失效 1:生效',
    `reason`      text        NOT NULL COMMENT '封禁原因',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;



ALTER TABLE `zb_member`
    ADD COLUMN `level_exp` decimal(15, 2) NOT NULL default 0 COMMENT '等级虚拟币消费' after freecoin;
update zb_member
set level_exp = freecoin;

ALTER TABLE `zb_siteconfig`
    ADD COLUMN `apkmuaversion` varchar(10) NOT NULL COMMENT 'apkmua版本号(安卓版本)' after iosaddress;
ALTER TABLE `zb_siteconfig`
    ADD COLUMN `apkmuaaddress` varchar(400) NOT NULL COMMENT 'apkmua地址(安卓地址)' after apkmuaversion;



#1
.
0.6
CREATE TABLE `zb_level_privilege`
(
    `id`              int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '会员特权列表id',
    `level`           tinyint(1) NOT NULL COMMENT '等级特权标识',
    `title`           varchar(60) COLLATE utf8_bin    NOT NULL DEFAULT '' COMMENT '等级特权名称',
    `picture`         varchar(100) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '等级特权图片',
    `preview_picture` varchar(100) COLLATE utf8_bin   NOT NULL COMMENT '等级预览图片',
    `content`         varchar(200) COLLATE utf8_bin   NOT NULL COMMENT '等级预览文案',
    `sort`            int(3) NOT NULL DEFAULT '0' COMMENT '特权排序',
    `status`          int(2) NOT NULL DEFAULT '0' COMMENT '0 禁用 1 启用',
    `ctime`           int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
    `updateTime`      int(11) NOT NULL DEFAULT 0 COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB COMMENT='等级特权信息';

ALTER TABLE `zb_attire`
    ADD COLUMN `list_type` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0:商城装扮 1:活动装扮 2:VIP/VIP装扮 3:贵族装扮' after `order`;
ALTER TABLE `zb_attire`
    ADD COLUMN `corner_sign` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '角标' after `attire_move_image`;
ALTER TABLE `zb_attire`
    ADD COLUMN `exp_time` int(11) NOT NULL DEFAULT 0 COMMENT '过期时间' after `updated_time`;



#1
.
0.7
CREATE TABLE `zb_room_music`
(
    `id`       int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `music_id` int(11) NOT NULL DEFAULT '0' COMMENT '音乐id',
    `room_id`  int(11) NOT NULL DEFAULT '0' COMMENT '房间id',
    `ctime`    int(11) NOT NULL DEFAULT '0' COMMENT '加入时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='房间收藏音乐列表';


CREATE TABLE `zb_user_online_census`
(
    `id`            int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `user_id`       int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `date`          datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '日期',
    `online_second` int(11) NOT NULL DEFAULT 0 COMMENT '累计时间',
    PRIMARY KEY (`id`),
    KEY             `user_id` (`user_id`) USING BTREE,
    KEY             `date` (`date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户在线时长统计表';

CREATE TABLE `zb_user_online_room_census`
(
    `id`            int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `user_id`       int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `room_id`       int(11) NOT NULL DEFAULT '0' COMMENT '房间id',
    `date`          datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '日期',
    `online_second` int(11) NOT NULL DEFAULT 0 COMMENT '累计时间',
    PRIMARY KEY (`id`),
    KEY             `user_id` (`user_id`) USING BTREE,
    KEY             `date` (`date`) USING BTREE,
    KEY             `room_id` (`room_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户在线房间时长统计表';

ALTER TABLE `zb_attire`
    ADD COLUMN `attire_android_image` varchar(255) NOT NULL DEFAULT '' COMMENT '装扮安卓图片' after `attire_image`;
ALTER TABLE `zb_member_song`
    ADD COLUMN `size` varchar(255) NOT NULL DEFAULT '' COMMENT '文件大小' after `attire_image`;

CREATE TABLE `zb_login_feedback`
(
    `id`      int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
    `account` int(11) NOT NULL COMMENT '用户账号',
    `status`  int(11) NOT NULL DEFAULT '1' COMMENT '状态：1展示 0不',
    `phone`   varchar(13)  NOT NULL DEFAULT '' COMMENT '手机号',
    `problem` varchar(255) NOT NULL DEFAULT '' COMMENT '问题',
    `mode`    varchar(30)  NOT NULL DEFAULT '' COMMENT '登陆方式',
    `addtime` datetime              DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户登陆反馈';

alter table zb_attire
    add column `activity_url` varchar(500) NOT NULL COMMENT '节日装扮活动地址';



CREATE TABLE `zb_user_identity`
(
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `uid`            int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `certname`       varchar(60) NOT NULL DEFAULT '' COMMENT '身份证姓名',
    `certno`         varchar(20) NOT NULL DEFAULT '' COMMENT '身份证号码',
    `outer_order_no` varchar(60) NOT NULL DEFAULT '' COMMENT '订单号',
    `certifyid`      varchar(60) NOT NULL DEFAULT '' COMMENT '身份核验流水号',
    `status`         int(10) NOT NULL DEFAULT '0' COMMENT '状态0：失败 1：成功 2：待确认',
    `create_time`    int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='实名认证表';


CREATE TABLE `zb_user_gift_wall`
(
    `uid`    int(11) NOT NULL COMMENT '用户ID',
    `giftid` int(11) NOT NULL COMMENT '礼物ID',
    `count`  int(11) NOT NULL COMMENT '收到的礼物数量',
    UNIQUE KEY `uid_giftid` (`uid`, `giftid`) USING BTREE,
    KEY      `giftid` (`giftid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='礼物墙';


CREATE TABLE `zb_task_checkin`
(
    `uid`         int(11) NOT NULL COMMENT '用户ID',
    `taskId`      int(11) NOT NULL COMMENT '任务id',
    `progress`    int(11) NOT NULL COMMENT '任务进度',
    `finishCount` int(11) NOT NULL COMMENT '任务完成次数',
    `finishTime`  int(11) NOT NULL COMMENT '任务完成时间',
    `gotReward`   int(11) NOT NULL COMMENT '是否领取奖励',
    `updateTime`  int(11) NOT NULL COMMENT '任务更新时间',
    UNIQUE KEY `uid_taskId` (`uid`, `taskId`) USING BTREE,
    KEY           `taskId` (`taskId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到任务';

CREATE TABLE `zb_task_daily`
(
    `uid`         int(11) NOT NULL COMMENT '用户ID',
    `taskId`      int(11) NOT NULL COMMENT '任务id',
    `progress`    int(11) NOT NULL COMMENT '任务进度',
    `finishCount` int(11) NOT NULL COMMENT '任务完成次数',
    `finishTime`  int(11) NOT NULL COMMENT '任务完成时间',
    `gotReward`   int(11) NOT NULL COMMENT '是否领取奖励',
    `updateTime`  int(11) NOT NULL COMMENT '任务更新时间',
    UNIQUE KEY `uid_taskId` (`uid`, `taskId`) USING BTREE,
    KEY           `taskId` (`taskId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日常任务';

CREATE TABLE `zb_task_newer`
(
    `uid`         int(11) NOT NULL COMMENT '用户ID',
    `taskId`      int(11) NOT NULL COMMENT '任务id',
    `progress`    int(11) NOT NULL COMMENT '任务进度',
    `finishCount` int(11) NOT NULL COMMENT '任务完成次数',
    `finishTime`  int(11) NOT NULL COMMENT '任务完成时间',
    `gotReward`   int(11) NOT NULL COMMENT '是否领取奖励',
    `updateTime`  int(11) NOT NULL COMMENT '任务更新时间',
    UNIQUE KEY `uid_taskId` (`uid`, `taskId`) USING BTREE,
    KEY           `taskId` (`taskId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新手任务';

CREATE TABLE `zb_task_activebox`
(
    `uid`         int(11) NOT NULL COMMENT '用户ID',
    `taskId`      int(11) NOT NULL COMMENT '任务id',
    `progress`    int(11) NOT NULL COMMENT '任务进度',
    `finishCount` int(11) NOT NULL COMMENT '任务完成次数',
    `finishTime`  int(11) NOT NULL COMMENT '任务完成时间',
    `gotReward`   int(11) NOT NULL COMMENT '是否领取奖励',
    `updateTime`  int(11) NOT NULL COMMENT '任务更新时间',
    UNIQUE KEY `uid_taskId` (`uid`, `taskId`) USING BTREE,
    KEY           `taskId` (`taskId`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='活跃开宝箱任务';

CREATE TABLE `zb_reward_record`
(
    `id`         int(11) NOT NULL AUTO_INCREMENT,
    `uid`        int(11) NOT NULL COMMENT '获得人id',
    `rewardId`   varchar(255) NOT NULL COMMENT '奖品资产id',
    `consumeId`  varchar(255) NOT NULL COMMENT '消耗资产id',
    `createTime` int(20) NOT NULL DEFAULT '0',
    `price`      int(11) NOT NULL DEFAULT '0' COMMENT '花费的金币数',
    `count`      int(11) NOT NULL DEFAULT '0' COMMENT '获得礼物个数',
    `from`       varchar(255) NOT NULL DEFAULT '' COMMENT '从哪个商城来的',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商城兑换记录';

ALTER TABLE `zb_member`
    ADD COLUMN `leave_time` datetime DEFAULT NULL COMMENT '退出时间' after `login_time`;

alter table zb_member
    ADD COLUMN `push_notice` tinyint(1) NOT NULL DEFAULT 1  COMMENT '推送开关0关 1开';

alter table zb_gift
    ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 0  COMMENT '活动礼物 0为否1为是';

CREATE TABLE `zb_turntable_pools`
(
    `turntable_id`  char(32)     NOT NULL COMMENT '转盘id',
    `pool_id`       int(11) NOT NULL COMMENT '池子ID',
    `gifts`         varchar(512) NOT NULL DEFAULT '' COMMENT '礼物权重json',
    `refresh_time`  bigint(20) NOT NULL DEFAULT '0' COMMENT '最后刷新时间',
    `refresh_count` int(11) NOT NULL DEFAULT '0' COMMENT '刷新次数',
    UNIQUE KEY `turntable_id_pool_id` (`turntable_id`,`pool_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='转盘礼物池子数据';

CREATE TABLE `yyht_turntable_re_user_gift`
(
    `id`           int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id`      int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
    `turntable_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '转盘id',
    `gift_id`      int(11) NOT NULL DEFAULT '0' COMMENT '礼物ID',
    `created`      int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `create_user`  varchar(30) CHARACTER SET utf8 NOT NULL COMMENT '创建用户',
    `updated`      int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
    `update_user`  varchar(30) CHARACTER SET utf8 NOT NULL COMMENT '更新用户',
    `state`        tinyint(1) NOT NULL DEFAULT '1' COMMENT '1正常，2处理中，3已发出',
    PRIMARY KEY (`id`) USING BTREE,
    KEY            `idx_user_id` (`user_id`) USING BTREE,
    KEY            `idx_turntable_id` (`turntable_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=393 DEFAULT CHARSET=utf8mb4 COMMENT='指定用户转出指定礼物'


alter table zb_monitoring
    ADD COLUMN `pwd` int(10) NOT NULL DEFAULT 0  COMMENT '未加密密码' after monitoring_pwd;

alter table zb_banner
    ADD COLUMN `location` int(10) NOT NULL DEFAULT 1  COMMENT '房间内banner位置';


CREATE TABLE `zb_nickname_library`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `hashkey`     varchar(32)  NOT NULL DEFAULT '""' COMMENT 'hashkey',
    `nickname`    varchar(150) NOT NULL DEFAULT '""' COMMENT '昵称',
    `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
    `use`         tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否使用过0,未使用 1使用过',
    `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `hashkey` (`hashkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户默认昵称库';

CREATE TABLE `zb_avatar_library`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `href`        varchar(255) DEFAULT '""' COMMENT '图片地址',
    `sex`         tinyint(1) DEFAULT '0' COMMENT '性别1 男 2女',
    `create_time` int(10) DEFAULT '0' COMMENT '创建时间',
    `status`      tinyint(1) DEFAULT '0' COMMENT '0禁用1启用',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='完善资料的用户默认头像库';


alter table zb_member change login_status nickname_hash varchar (32) default NULL COMMENT 'nickname hash';

update zb_member
set nickname_hash=null;

CREATE TABLE `zb_gashapon_reward`
(
    `id`           int(11) NOT NULL AUTO_INCREMENT,
    `uid`          int(11) NOT NULL DEFAULT '0' COMMENT '获得人id',
    `reward_id`    varchar(255) NOT NULL DEFAULT '' COMMENT '奖品assetId',
    `reward_count` int(255) NOT NULL DEFAULT '0' COMMENT '获得礼物个数',
    `create_time`  int(20) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY            `create_time` (`create_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='扭蛋机奖励表';

alter table zb_chargedetail
    ADD COLUMN `paid_time` int(20) NOT NULL DEFAULT '0'  COMMENT '支付成功时间';
alter table zb_chargedetail MODIFY COLUMN `finish_time` int (20) NOT NULL DEFAULT '0' COMMENT '发货时间';

alter table zb_user_last_info
    add edition varchar(20) not null default "" COMMENT "client version";

alter table zb_member modify vip_exp bigint(20) default 0;
alter table zb_member modify svip_exp bigint(20) default 0;

alter table zb_forum modify forum_image varchar (500) NOT NULL DEFAULT '' COMMENT '图片json';

CREATE TABLE `zb_delivery_address`
(
    `id`            int(11) NOT NULL AUTO_INCREMENT,
    `user_id`       int(11) NOT NULL DEFAULT '0' COMMENT '填些单子的人',
    `name`          varchar(255) NOT NULL DEFAULT '' COMMENT '收货人',
    `mobile`        varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
    `reward`        varchar(255) NOT NULL DEFAULT '' COMMENT '奖品kindId或者是奖品描述',
    `count`         varchar(255) NOT NULL DEFAULT '' COMMENT '奖品数量',
    `region`        varchar(255) NOT NULL DEFAULT '' COMMENT '地区',
    `address`       varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
    `activity_type` varchar(255) NOT NULL DEFAULT '' COMMENT '活动类型:哪个活动得的',
    `create_time`   int(20) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY             `user_id` (`user_id`) USING BTREE,
    KEY             `create_time` (`create_time`) USING BTREE,
    KEY             `activity_type` (`activity_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收货地址表';


update zb_member
set birthday="2003-01-01"
where birthday > "2003-09-01" limit 50000;


CREATE TABLE `zb_cancel_detail`
(
    `id`                 int(11) unsigned NOT NULL AUTO_INCREMENT,
    `qopenid`            varchar(32)  NOT NULL DEFAULT '' COMMENT 'qq',
    `wxopenid`           varchar(32)  NOT NULL DEFAULT '' COMMENT 'wechat',
    `appleid`            varchar(255) NOT NULL DEFAULT '' COMMENT '苹果id',
    `username`           varchar(20)  NOT NULL DEFAULT '' COMMENT 'username',
    `userid`             int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `cancel_user_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '⽤户注销账户状态 0：⽆操作 1：已通过 2：审核中 (default:2)',
    `cancellation_time`  int(11) NOT NULL DEFAULT '0' COMMENT '申请注销的时间',
    `update_time`        int(11) NOT NULL DEFAULT '0' COMMENT '通过注销的时间',
    PRIMARY KEY (`id`),
    KEY                  `userid_key` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='注销申请记录表';

alter table zb_member MODIFY COLUMN `qopenid` varchar (50) NOT NULL DEFAULT '';

alter table zb_member MODIFY COLUMN `wxopenid` varchar (50) NOT NULL DEFAULT '';



CREATE TABLE `zb_recall_sms_detail`
(
    `id`                int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id`           int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
    `origin_login_time` int(10) NOT NULL DEFAULT '0' COMMENT '召回前的登陆时间',
    `sms_status`        tinyint(1) NOT NULL DEFAULT '0' COMMENT '短信发送是否成功 0失败 1 成功',
    `sms_detail`        varchar(255) NOT NULL DEFAULT '' COMMENT '短信发送response',
    `login_time`        int(10) NOT NULL DEFAULT '0' COMMENT '回归登陆的时间',
    `send_gift`         tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否发送头像框0没有1已送',
    `create_time`       int(10) NOT NULL DEFAULT '0' COMMENT 'create_time',
    `update_time`       int(10) NOT NULL DEFAULT '0' COMMENT 'update_time',
    PRIMARY KEY (`id`),
    UNIQUE KEY `userid_key` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='短信召回活动的用户数据详情';


CREATE TABLE `zb_recall_sms_info`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id`     int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    `platform`    varchar(60) NOT NULL DEFAULT '' COMMENT '平台',
    `action`      varchar(60) NOT NULL DEFAULT '' COMMENT '行为 click：点击 download：下载',
    `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY           `userid_key` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='用户短信召回行为上报表';


CREATE TABLE `zb_member_detail_audit`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id`     int(11) unsigned NOT NULL COMMENT '用户id',
    `content`     varchar(255) NOT NULL DEFAULT '' COMMENT '数据内容',
    `status`      tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0未审核,1审核通过,2未通过',
    `action`      varchar(30)  NOT NULL DEFAULT '' COMMENT '行为(avatar,nickname,intro,wall)',
    `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
    `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `useridaction_key` (`user_id`,`action`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='用户信息详情审核记录表';

CREATE TABLE `zb_room_pk`
(
    `id`            int(11) unsigned NOT NULL AUTO_INCREMENT,
    `red_room_id`   int(11) unsigned NOT NULL DEFAULT '0' COMMENT '红队房间id',
    `blue_room_id`  int(11) unsigned NOT NULL DEFAULT '0' COMMENT '蓝队房间id',
    `pk_mode`       int(11) unsigned NOT NULL COMMENT 'pk模式 1团战 2跨房',
    `create_time`   bigint(20) NOT NULL DEFAULT '0' COMMENT '开始时间',
    `end_time`      bigint(20) NOT NULL DEFAULT '0' COMMENT '结束时间',
    `punishment`    varchar(255) DEFAULT '' COMMENT '惩罚',
    `win_team`      varchar(30) DEFAULT '' COMMENT '赢的队 red-红 blue-蓝',
    `red_pk_data`   varchar(255) NOT NULL DEFAULT '' COMMENT '红队pk数据',
    `red_contribute_data`   varchar(255) NOT NULL DEFAULT '' COMMENT '红队贡献数据',
    `blue_pk_data`          varchar(255) NOT NULL DEFAULT '' COMMENT '蓝队pk数据',
    `blue_contribute_data`  varchar(255) NOT NULL DEFAULT '' COMMENT '蓝队贡献数据',
    PRIMARY KEY (`id`),
    UNIQUE KEY `red_room_id_blue_room_id_create_time` (`red_room_id`,`blue_room_id`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='pk数据';

CREATE TABLE `zb_check_im_message` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `from_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发送者id',
   `to_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接收者id',
   `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '消息类型 0文本消息 1图片消息  2语音消息 3视频消息 4位置消息 5文件消息 6提示消息 7自定义消息',
   `message` text COLLATE utf8_bin COMMENT '消息内容',
   `check_response` text COLLATE utf8_bin COMMENT '检测结果',
   `api_response` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '接口返回信息',
   `status` tinyint(1) DEFAULT NULL COMMENT '消息状态 1发送成功 2检测失败 3信息限制 4撤回',
   `created_time` int(20) DEFAULT '0' COMMENT '创建时间',
   `updated_time` int(20) DEFAULT '0' COMMENT '更改时间',
   `ext_1` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '预留字段1',
   `ext_2` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '预留字段2',
   `ext_3` varchar(255) COLLATE utf8_bin DEFAULT '' COMMENT '预留字段3',
   PRIMARY KEY (`id`),
   KEY `idx_createdtime` (`created_time`) USING BTREE,
   KEY `idx_fromuid` (`from_uid`) USING BTREE,
   KEY `idx_touid` (`to_uid`) USING BTREE,
   KEY `idx_type` (`type`) USING BTREE,
   KEY `idx_status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='IM消息';

CREATE TABLE `zb_room_photo`
(
    `id`       int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '图片id',
    `user_id`  int(11) NOT NULL COMMENT '上传图片的用户id',
    `room_id`  int(11) NOT NULL COMMENT '房间id',
    `image` varchar(255) NOT NULL COMMENT '图片',
    `gift_id`  int(11) NOT NULL COMMENT '解锁图片需要的礼物id',
    `status`    tinyint(1) NOT NULL COMMENT '图片状态 1审核中 2审核成功 3审核失败',
    `create_time`    int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_room_id` (`room_id`) USING BTREE,
    KEY `idx_status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='房间相册';

CREATE TABLE `zb_user_account_map`
(
    `type`       varchar(11) binary NOT NULL DEFAULT '0' COMMENT '类型 如手机号：mobile 第三方：snsType',
    `value`      varchar(60) binary NOT NULL DEFAULT '0' COMMENT '类型的值 如：moblie就是手机号 snsType就是微信支付宝的key',
    `user_id`     int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    UNIQUE KEY `idx_type_value` (`type`,`value`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录相关账号映射表';

CREATE TABLE `zb_room_info_map` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'pk',
  `type` varchar(11) binary NOT NULL DEFAULT '0' COMMENT '类型 如工会id：guild_id 用户id：user_id',
  `value` varchar(60) binary NOT NULL DEFAULT '0' COMMENT '类型的值',
  `room_id` int(11) NOT NULL DEFAULT '0' COMMENT '房间id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_id_type` (`room_id`,`type`),
  KEY `idx_type_value` (`type`,`value`)
) ENGINE=InnoDB AUTO_INCREMENT=111764 DEFAULT CHARSET=utf8mb4 COMMENT='房间信息相关账号映射表';

CREATE TABLE `zb_user_info_map`
(
    `type`       varchar(11) binary NOT NULL DEFAULT '0' COMMENT '类型 如靓号：pretty 昵称：nickname',
    `value`      varchar(60) binary NOT NULL DEFAULT '0' COMMENT '类型的值',
    `user_id`     int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
    UNIQUE KEY `idx_type_value` (`type`,`value`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户信息相关账号映射表';

CREATE TABLE `zb_user_attention` (
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `attention_id` int(11) NOT NULL COMMENT '被关注用户id',
  `create_time` int(20) NOT NULL DEFAULT '0' COMMENT '创建时间',
  UNIQUE KEY `user_id_attention_id` (`user_id`,`attention_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户关注数据表结构';

CREATE TABLE `zb_user_fans` (
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `fans_id` int(11) NOT NULL COMMENT '粉丝用户id',
  `is_read` int(2) NOT NULL DEFAULT '0' COMMENT '区分已读纬度  0未读 1 已经读',
  `create_time` int(20) NOT NULL DEFAULT '0' COMMENT '创建时间',
  UNIQUE KEY `user_id_fans_id` (`user_id`,`fans_id`) USING BTREE,
  KEY `a` (`user_id`,`is_read`) USING BTREE,
  KEY `userId` (`user_id`,`fans_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户粉丝数据表结构';

CREATE TABLE `zb_user_friend` (
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `friend_id` int(11) NOT NULL COMMENT '好友用户id',
  `create_time` int(20) NOT NULL DEFAULT '0' COMMENT '创建时间',
  UNIQUE KEY `user_id_friend_id` (`user_id`,`friend_id`) USING BTREE,
  KEY `userId` (`user_id`,`friend_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户好友数据表结构';