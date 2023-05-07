<?php

namespace app\domain\mall\dao;

class MallBuyRecordModel
{
    public $id = 0;
    public $userId = 0;
    // 奖品assetId
    public $rewardId = '';
    // 消耗assetId
    public $consumeId = '';
    //时间
    public $createTime= 0;
    //花费的金币数
    public $price = 0;
    // 获得礼物个数
    public $count = 0;
    // 从哪来
    public $mallId = '';
    // 附加字段
    public $from = '';

    public function __construct($userId, $rewardId='', $count=0, $consumeId='', $price=0, $mallId='', $from='',$createTime=0) {
        $this->userId = $userId;
        $this->rewardId = $rewardId;
        $this->price = $price;
        $this->count = $count;
        $this->mallId = $mallId;
        $this->consumeId = $consumeId;
        $this->from = $from;
        $this->createTime = $createTime;
    }
}