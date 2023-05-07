<?php


namespace app\domain\vip;


use app\utils\ArrayUtil;

class VipPrivilegeGroup
{
    public $id = '';
    public $title = '';
    public $content = '';
    public $pic = '';
    // 0 禁用 1 启用
    public $status = 0;

    public function decodeFromJson($jsonObj) {
        $this->id = $jsonObj['id'];
        $this->title = $jsonObj['title'];
        $this->content = $jsonObj['content'];
        $this->pic = $jsonObj['pic'] ?? '';
        $this->status = $jsonObj['status'];
    }
}