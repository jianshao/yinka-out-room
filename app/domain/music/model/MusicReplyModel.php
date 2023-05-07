<?php


namespace app\domain\music\model;


class MusicReplyModel
{
    // id
    public $id = 0;
    // 歌手
    public $songer = '';
    // 歌曲名
    public $songName = '';
    // 音乐时长
    public $songTime = 0;
    // url
    public $songUrl = '';
    // 下载量
    public $downloadNum = 0;
    // 乐状态 [伴奏,原唱]
    public $typeName = "";
    // 上传者
    public $upname = '';

    public $star=0;
    // 文件大小
    public $size = '';
    //单位
    public $unit="";
}