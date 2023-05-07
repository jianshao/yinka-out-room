<?php

namespace app\domain\models;

class BlackListModel
{
    // 后台人员id
    public $adminId = 0;
    // 封禁时间
    public $time = 0;
    // 封禁时id
    public $kickId = 0;
    // 黑名单uid
    public $userId = 0;
    // 创建时间
    public $createTime = 0;
    // 更新时间
    public $updateTime = 0;
    // 是否封禁状态（1封禁状态，2解禁）默认为1
    public $status = 0;
    // 封禁理由
    public $desc = '';
}