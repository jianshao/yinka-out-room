<?php


namespace app\domain\census\model;


class RoomWholeWheatModel
{
    //送礼人id
    public $sendUid = 0;
    //房间id
    public $roomId = 0;
    //礼物id
    public $giftId = 0;
    //礼物数量
    public $count = 0;
    //礼物价值
    public $giftValue = 0;
    //送礼时间
    public $createTime = 0;
    //ext 备用字段
    public $ext = '';

    public function __construct($sendUid = 0, $roomId = 0 , $giftId = 0, $count= 0,  $giftValue = 0, $createTime = 0, $ext = '') {
        $this->sendUid    = $sendUid;
        $this->roomId     = $roomId;
        $this->giftId     = $giftId;
        $this->count      = $count;
        $this->giftValue  = $giftValue;
        $this->createTime = $createTime;
        $this->ext        = $ext;
    }
}