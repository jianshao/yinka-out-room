<?php


namespace app\domain\user\model;


class FansModel
{
    // 用户id
    public $userId = 0;
    // 粉丝用户id
    public $fansId = 0;
    // 创建时间
    public $createTime = 0;
    // 区分已读未读  0未读 1 已经读
    public $isRead = 0;
}