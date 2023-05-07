<?php


namespace app\domain\room\model;


class RoomTypeModel
{
    public $id = 0;
    // 父类型ID
    public $pid = 0;
    // 房间模式名称
    public $roomMode = '';
    // 创建时间
    public $createTime = 0;
    // 模式分类 1 普通用户 2公会用户
    public $modeType = 1;
    // 用于首页 1首页 2未首页(在创建列表没有)
    public $status = 1;
    // 排序
    public $isSort = 0;
    // 麦位数量
    public $micCount = 9;
    // 标签图标
    public $tabIcon = '';
    // 1 分类显示 2其他不显示
    public $isShow = 1;
    // 是否默认类型1 否 2是
    public $type = 1;
}