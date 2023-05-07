<?php

namespace app\domain\user\model;

//用户信息详情审核记录模型
class MemberDetailModel
{
    // id
    public $id = 0; //主键
    // 用户id
    public $userId = 0; //用户id
    // 充值豆数量
    public $amount = 0;
//    设备oaid（安卓）
    public $oaid = "";

//    修改时间
    public $updateTime = 0;
//    创建时间
    public $createTime = 0;
}



