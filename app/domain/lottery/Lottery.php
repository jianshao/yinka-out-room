<?php


namespace app\domain\lottery;


use app\domain\asset\AssetItem;

class Lottery
{
    //id
    public $id = 0;
    //权重
    public $weight= 0;
    //名称
    public $name= 0;
    //图片
    public $image= 0;
    //奖励
    public $reward= null;

    public function loadFromJson($objson) {
        $this->id = $objson['id'];
        $this->weight = $objson['weight'];
        $this->name = $objson['name'];
        $this->image = $objson['img'];
        $this->reward = new AssetItem($objson['reward']['assetId'], $objson['reward']['count']);
    }
}