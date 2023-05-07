<?php

namespace app\domain\user\model;

class UserModel
{
    // 用户ID
    public $userId = 0;
    // 靓号
    public $prettyId = 0;
    // 用户名
    public $username = '';
    // 密码
    public $password = '';
    // 昵称
    public $nickname = '';
    // 昵称hash
    public $nicknameHash = '';
    // 性别 1: 男 2: 女 3:保密
    public $sex = 0;
    // 简介
    public $intro = '';
    // 头像
    public $avatar = '';
    public $prettyAvatar = '';
    public $prettyAvatarSvga = '';
    // 1 未封禁 2 封禁
    public $status = 1;
    // 1 公会用户 2普通用户 9527超级管理员
    public $role = 0;
    // 生日
    public $birthday = '';
    // 城市
    public $city = '';
    // DEFAULT '1'  lv等级(用户充值行为提升用户等级)暂时为计算
    public $lvDengji = 1;
    // 手机号
    public $mobile = '';
    // 网易云id
    public $accId = 0;
    // 经验值
    public $levelExp = 0;
    // vip级别，0 非会员 1vip  2svip
    public $vipLevel = 0;
    public $vipExpiresTime = 0;
    public $svipExpiresTime = 0;
    // 注册时间
    public $registerTime = 0;
    // 注册ip
    public $registerIp = '';
    // 登录时间
    public $loginTime = 0;
    // 登录ip
    public $loginIp = '';
    //deviceid
    public $deviceId = '';
    // 0: 未注销 1: 注销
    public $isCancel = 0;
    // 注销账号 0: 无操作  1: 已通过 2: 审核中 3: 未通过
    public $cancelStatus = 0;
    // 注销时间 int  unix
    public $cancellationTime = 0;
    // 0 未提交 1认证 2未通过 3审核中
    public $attestation = 0;
    // 邀请码
    public $inviteCode = '';
    public $registerChannel = '';
    // 注册版本号
    public $registerVersion = '';
    public $imei = '';
    public $idfa = '';

    public $qopenid = '';
    public $wxopenid = '';
    public $wxunionid = '';
    public $appleid = '';

    // 工会Id（0为普通用户)
    public $guildId = 0;
    // 工会个人分成
    public $socity = 0;
    // 银锤子数量
    public $hammers = 0;
    // 金锤子
    public $smasheggs = 0;

    public $roomId = 0;
    public $zyUid = 0;
    public $dukeLevel = 0;
    public $dukeValue = 0;
    public $dukeExpiresTime = 0;
    // 推送开关0关 1开
    public $pushNotice = 0;
    public $unablechat = 0;
    //包名
    public $source = 'fanqie';
    public $voiceIntro = '';
    public $voiceTime  = 0;
//    隐身状态
    public $online =0;
}


