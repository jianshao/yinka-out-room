<?php


namespace app\domain\activity\duobao3;


class Order
{
    public $issueNum = 0;
    public $giftId = 0;
    public $giftCoin = 0;
    public $giftName = '';
    public $giftImage = '';
    public $price = 0;
    public $seatCount = 0;
    public $status = 0;
    public $createTime = 0;
    // list<SeatInfo>
    public $seatInfos = [];
    public $winnerIndex = 0;
}