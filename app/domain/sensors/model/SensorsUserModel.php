<?php


namespace app\domain\sensors\model;


class SensorsUserModel
{
    // 用户uid
    public $userId = 0;

    // 注册时间
    public $registerTime = '';

    // 渠道来源
    public $channel = '';

    // 性别
    public $sex = '';

    // 生日
    public $birthday = '';

    // 昵称
    public $nickname = '';

    // 星座
    public $constellation = '';

    // 个推ID
    public $geTuiId = '';

    // 手机号
    public $mobile = '';

    // 资料完整度
    public $information = 0;

    // 年龄
    public $age = 0;

    // 身份
    public $role = '用户';

    // 关注数
    public $followNum = 0;

    // 好友数
    public $friendNum = 0;

    // 粉丝数
    public $fansNum = 0;

    // 主播魅力等级
    public $charmLevel = '1';

    // 会员到期日期
    public $vipEndTime = 0;

    // 会员等级
    public $vipLevel = '';

    // 账户余额
    public $balance = 0;

    // 会员类型
    public $vipType = '';

    // 所属公会
    public $guild = [];

    // 是否青少年
    public $isOpenTeenagers = false;

    // 是否声音提醒
    public $isOpenSound = false;

    // 累计发动态次数
    public $addForumNum = 0;

    // 累计点赞数
    public $likeNum = 0;

    // 累计签到数
    public $signInNum = 0;

}