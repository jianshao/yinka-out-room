<?php


namespace app\query\banner;


class BannerModel
{
    public $id = 0;
    public $type = 0;
    public $image = '';
    public $linkUrl = '';
    public $title = '';
    public $channel = '';
    public $createTime = 0;
    public $startTime = 0;
    public $endTime = 0;
    public $showType = '';
    public $status = 0;
    # 活动类型 如宝箱=box2 转盘=turntable
    public $bannerType = 0;
    # 房间内banner位置 banner有两个位置 如：转盘在1号位banner 宝箱2号位banner
    public $location = 0;
}