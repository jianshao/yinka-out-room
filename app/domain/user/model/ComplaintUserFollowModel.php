<?php

namespace app\domain\user\model;


/**
 * @Info 举报用户跟进模型
 * Class ComplaintUserModel
 * @package app\domain\user\model
 */
class ComplaintUserFollowModel
{
    public $id = 0;// id

    public $cid = 0;// 举报记录的id

    public $content = "";// 消息内容

    public $adminId = 0;// 后台操作人id

    public $createTime = 0;// 创建时间
}


