<?php

namespace app\domain\notice;


class Notice
{
    public $id = 0;
    public $title = '';
    public $image = '';
    public $content = '';
    //发布状态（1发布，2未发布，3已删除，4已恢复）
    public $status = 2;
    //定时发布开始时间
    public $timingTime = 0;
    public $createTime= 0;
    public $createUser = '';
    public $updateTime = 0;
    public $updateUser = '';
    //活动地址
    public $jumpUrl = '';
}