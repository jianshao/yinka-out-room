<?php

namespace app\domain\notice\model;

//第三方推送模型
class PushTemplateModel
{
    // 主键
    public $id = 0;
    // 第三方原始id
    public $originId = '';
    // 消息标题
    public $title = '';
    // 消息内容
    public $content = '';
    // 消息类型  (创蓝短信/容通达/push/ai电话)
    public $type = '';
    // 创建时间
    public $createTime = 0;
    // 修改时间
    public $updateTime = 0;
//    模版名称
    public $template_name = "";
}


