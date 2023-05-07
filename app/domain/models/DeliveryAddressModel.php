<?php

namespace app\domain\models;

class DeliveryAddressModel
{
    // 谁填的单子
    public $userId = 0;
    // 收货人
    public $name = 0;
    // 手机号
    public $mobile = '';
    // 地区
    public $region = '';
    // 地址
    public $address = '';
    // 奖品assetId或者是奖品描述
    public $reward = '';
    // 奖品数量
    public $count = 0;
    // 活动类型
    public $activityType = '';
    public $createTime = 0;
}