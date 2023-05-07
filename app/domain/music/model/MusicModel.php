<?php


namespace app\domain\music\model;


class MusicModel
{
    // id
    public $id = 0;
    // 用户ID
    public $userId = 0;
    // 歌手
    public $songer = '';
    // 歌曲名
    public $songName = '';
    // 审核状态 0未审核1通过2删除
    public $status = 0;
    // 音乐时长
    public $songTime = 0;
    // 创建时间
    public $createTime = 0;
    // url
    public $songUrl = '';
    // 审核人
    public $examineUid = '';
    // 审核时间
    public $examineTime = 0;
    // 下载量
    public $downloadNum = 0;
    // 乐状态 1伴奏 2原唱
    public $type = 0;
    // 0下架 1上架
    public $sale = 0;
    // 上传者
    public $upname = '';
    // 文件大小
    public $size = '';
}