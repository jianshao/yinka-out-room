<?php

namespace app\domain\user\model;

//用户信息详情审核记录模型
class MemberDetailAuditModel
{
    // id
    public $id = 0; //主键
    // 用户id
    public $userId = 0; //用户id
    // 房间id
    public $roomId = 0;
    // 内容数据
    public $content = ''; // 内容数据
    // 状态
    public $status = 0; //状态0未审核1审核通过，2未通过
    // 行为
    public $action = '';//行为(avatar,nickname,intro,wall)
//    修改时间
    public $updateTime = 0; //'更新时间'
//    创建时间
    public $createTime = 0; //'创建时间'
    // 审核管理员ID  表中字段为admin_user_name
    public $adminUserId = 0;
}



