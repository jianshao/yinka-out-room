<?php

namespace app\domain\events;

/**
 * 用户道具事件
 */
class UserPropEvent
{
    public $userId = 0;

    public function __construct($userId) {
        $this->userId = $userId;
    }
}

/**
 * 用户道具添加事件
 */
class UserPropAddEvent extends UserPropEvent
{
    // 哪个用户
    public $userId = 0;
    // 哪个道具增加了
    public $prop = null;
    // 增加的数量
    public $count = 0;
    // 剩余数量
    public $balance = 0;

    public function __construct($userId, $prop, $count, $balance) {
        parent::__construct($userId);
        $this->prop = $prop; 
        $this->count = $count;
        $this->balance = $balance;
    }
}

/**
 * 用户道具消耗事件
 */
class UserPropConsumeEvent extends UserPropEvent
{
    // 哪个用户
    public $userId = 0;
    // 哪个道具消耗了
    public $prop = null;
    // 消耗的数量
    public $count = 0;
    // 剩余数量
    public $balance = 0;

    public function __construct($userId, $prop, $count, $balance) {
        parent::__construct($userId);
        $this->prop = $prop; 
        $this->count = $count;
        $this->balance = $balance;
    }
}


/**
 * 用户删除道具事件
 */
class UserPropRemoveEvent extends UserPropEvent
{
    // 哪个用户
    public $userId = 0;
    // 哪个道具删除了
    public $prop = null;

    public function __construct($userId, $prop) {
        parent::__construct($userId);
        $this->prop = $prop;
    }
}


