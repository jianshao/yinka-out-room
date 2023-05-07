<?php


namespace app\domain\vip;


use app\utils\ArrayUtil;

class VipPrivilegeDesc
{
    public $title = '';
    public $pid = '';   // 所属组
    public $picture = '';
    public $twoPicture = '';
    public $previewPicture = '';
    public $content = '';
    public $contentSmall = '';
    // 0 禁用 1 启用
    public $status = 0;
    // 0 没有 1 拥有
    public $have = 0;

    public function decodeFromJson($jsonObj) {
        $this->title = ArrayUtil::safeGet($jsonObj, 'title',"");
        $this->pid = ArrayUtil::safeGet($jsonObj, 'pid',"");
        $this->picture = ArrayUtil::safeGet($jsonObj, 'pic',"");
        $this->twoPicture = ArrayUtil::safeGet($jsonObj, 'twoPic',"");
        $this->previewPicture = ArrayUtil::safeGet($jsonObj, 'previewPic',"");
        $this->content = ArrayUtil::safeGet($jsonObj, 'content',"");
        $this->contentSmall = ArrayUtil::safeGet($jsonObj, 'contentSmall',"");
        $this->status = ArrayUtil::safeGet($jsonObj, 'status',"");
        $this->have = ArrayUtil::safeGet($jsonObj, 'have',0);
    }
}