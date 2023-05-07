<?php

namespace app\domain\user\model;


/**
 * @Info 举报用户模型
 * Class ComplaintUserModel
 * @package app\domain\user\model
 */
class ComplaintUserModel
{
    public $id = 0;// id

    public $fromUid = 0;// fromUid

    public $toUid = 0;// toUid

    public $contents = "";// 内容

    public $description = "";// 描述

    public $images = "";// 图片

    public $videos = "";// 视频

    public $createTime = 0;// id

    public $updateTime = 0;// id

    public $status = 0;// id

    public $adminId=0;
}


