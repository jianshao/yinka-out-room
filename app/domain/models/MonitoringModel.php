<?php

namespace app\domain\models;

class MonitoringModel
{
    // 自增id
    public $monitoringId = 0;
    // 用户ID
    public $userId = 0;
    // 监控密码（md5）
    public $monitoringPassword = '';
    // 监控密码 （未加密密码）
    public $password = '';
    // 监控状态 1为监控模式 0为非监控模式
    public $monitoringStatus = 0;
    // 家长模式密码
    public $parentsPassword = '';
    // 家长模式 1为开启，0为不开启
    public $parentStatus = 0;
    // 开启监控时间
    public $monitoringTime = 0;
    // 结束时间
    public $monitoringEndTime = 0;
    // 启上锁时间
    public $lockTime = 0;
    // 强制上锁 0不上锁，1上锁
    public $constraintLock = 0;
    // 0 正常，1待审核，2通过
    public $status = 0;
}