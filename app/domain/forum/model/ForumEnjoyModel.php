<?php


namespace app\domain\forum\model;


class ForumEnjoyModel
{
    public $id = 0;
    // 点赞人uid
    public $enjoyUid = 0;
    // 帖子id
    public $forumId = 0;
    // 状态: 0未读 1已读
    public $isRead = 0;
    //状态：0默认 1删除
    public $isDel = 0;
    // 创建时间
    public $createTime = 0;
    // 更新时间
    public $updateTime = 0;

}