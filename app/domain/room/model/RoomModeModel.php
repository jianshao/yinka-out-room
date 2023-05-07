<?php


namespace app\domain\room\model;


class RoomModeModel
{
//    id
    public $id = 0;
    // 父类型ID
    public $pid = 0;
    // 模式名称
    public $name = '';
    // muaimg
    public $imageMua = "";
    // yinlianimg
    public $imageYinlian = "";

    public function fromJson($jsonObj)
    {
        $this->id = isset($jsonObj['id']) ? $jsonObj['id'] : 0;
        $this->pid = isset($jsonObj['pid']) ? $jsonObj['pid'] : 0;
        $this->name = isset($jsonObj['tag_name']) ? $jsonObj['tag_name'] : "";
        $this->imageMua = isset($jsonObj['tag_img_mua']) ? $jsonObj['tag_img_mua'] : "";
        $this->imageYinlian = isset($jsonObj['tag_img_yinlian']) ? $jsonObj['tag_img_yinlian'] : "";
        return $this;
    }


}