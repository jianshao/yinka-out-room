<?php

namespace app\event;


//购买商品
class BuyGoodsEvent extends AppEvent
{
    public $userId = 0;
    public $goodsId = 0;
    //消耗的资产
    public $consumeAsset = null;
    //增加的资产
    public $addAsset = null;
    //购买的数量
    public $count = 0;
    // 从哪个商城来的
    public $mallId = '';
    //from: 从哪儿来的
    public $from = 0;
    public $roomId = 0;
    public $balance = 0;


    public function __construct($userId, $roomId, $goodsId, $consumeAsset, $addAsset, $count, $mallId, $from, $balance, $timestamp) {
        parent::__construct($timestamp);
        $this->userId = $userId;
        $this->roomId = $roomId;
        $this->goodsId = $goodsId;
        $this->consumeAsset = $consumeAsset;
        $this->addAsset = $addAsset;
        $this->count = $count;
        $this->mallId = $mallId;
        $this->from = $from;
        $this->balance = $balance;
    }
}