<?php


namespace app\domain\forum\model;


class ForumBlackModel
{
    // 拉黑操作人
    public $userId = 0;
    // 被拉黑userId
    public $toUserId = 0;
    // 创建时间
    public $createTime = 0;
    // 更新时间
    public $updateTime = 0;
}