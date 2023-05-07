<?php


namespace app\domain\forum\model;


class ForumModel
{
    public $forumId = 0;
    // 发帖人id
    public $forumUid = 0;
    // 内容
    public $content = 0;
    //图片
    public $image = '';
    // 语音url
    public $voice = '';
    // 语音时间
    public $voiceTime = 0;
    // 1正常2图片不通过3语音不通过4内容不通过5阿里调用失败
    public $aliExamine = 0;
    // 阿里审核时间
    public $aliExamineTime = 0;
    // 图片审核结
    public $aliExamineImgJson = null;
    // 语音审核结果
    public $aliExamineVoiceJson = null;
    // 自己删除
    public $selfDelUid = 0;
    //自己删除时间
    public $selfDelTime = 0;
    // 删除人uid
    public $delUid = 0;
    // 删除时间
    public $delTime = 0;
    // 帖子状态1正常2隐藏3未审核4删除5账户封禁删除
    public $status = 0;
    // 基础点赞数
    public $baseNum = 0;
    // 创建时间
    public $createTime = 0;
    // 更新时间
    public $updateTime = 0;
    // 审核通过时间
    public $examinedTime = 0;
    // 动态所属话题
    public $tid = 0;
    // 所属位置
    public $location = '';
    // 分享统计
    public $shareNum = 0;
    // 是否置顶  1:置顶
    public $isTop = 0;

}