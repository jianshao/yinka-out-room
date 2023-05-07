<?php


namespace app\domain\forum\model;


class ForumReplyModel
{
    //回复id
    public $replyId = 0;
    //回复uid
    public $replyUid = 0;
    // 回复内容
    public $content = '';
    // 帖子id
    public $forumId = 0;
    // 多级回复0顶级回复
    public $parentId = 0;
    // 删除人uid
    public $delUid = 0;
    // 删除时间
    public $delTime = 0;
    // 回复状态1正常2隐藏3删除
    public $status = 0;
    // 回复类型1回帖2评论
    public $type = 0;
    // 评论@uid
    public $atUid = 0;
    //状态: 0未读 1已读
    public $isRead = 0;
    // 创建时间
    public $createTime = 0;
    // 更新时间
    public $updateTime = 0;

}