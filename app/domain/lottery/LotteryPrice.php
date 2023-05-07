<?php


namespace app\domain\lottery;


use app\domain\asset\AssetItem;

class LotteryPrice
{
    //抽几次
    public $num = 0;
    //价格
    public $price = null;

    public function loadFromJson($objson) {
        $this->num = $objson['num'];
        $this->price = new AssetItem($objson['price']['assetId'], $objson['price']['count']);
    }
}