<?php


namespace app\domain\redpacket;


class RedPacketDetailInfo
{
    public $count = 0;
    public $getCount = 0;
    public $getDetailList = [];

    public function __construct($count, $getCount, $getDetailList) {
        $this->count = $count;
        $this->getCount = $getCount;
        $this->getDetailList = $getDetailList;
    }
}