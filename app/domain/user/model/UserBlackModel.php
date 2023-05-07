<?php


namespace app\domain\user\model;


use app\utils\TimeUtil;

class UserBlackModel
{
    // 用户ID
    public $userId = 0;
    //类型
    public $type = 1;
    // 注册ip
    public $blackinfo = 0;
    // 管理员id
    public $adminId = 0;
    // 创建时间
    public $createTime = 0;
    // 创建时间
    public $updateTime = 0;
    //封禁时长
    public $time = 0;
    // 记录状态 0:失效 1:封禁
    public $status = 0;
    // 封禁原因
    public $reason = 0;
    //封禁结束时间
    public $endTime = 0;
    //封禁时间
    public $blackTime = 0;

    public function __construct($userId) {
        $this->userId = $userId;
    }
//
//    public function adjust($timestamp) {
//        if (!TimeUtil::isSameDay($timestamp, $this->updateTime)) {
//            $this->diamond = 0;
//            $this->updateTime = $timestamp;
//            return true;
//        }
//        return false;
//    }
}