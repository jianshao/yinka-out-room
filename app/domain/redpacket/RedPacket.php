<?php


namespace app\domain\redpacket;


use app\utils\ArrayUtil;

class RedPacket
{
    public $value = 0;
    public $productId = 0;
    public $showType = 0;

    public function __construct($value=0, $productId=0, $showType=1) {
        $this->value = $value;
        $this->productId = $productId;
        $this->showType = $showType;
    }

    public function decodeFromJson($jsonObj) {
        $this->value = $jsonObj['value'];
        $this->productId = $jsonObj['productId'];
        $this->showType = ArrayUtil::safeGet($jsonObj, 'showType', 1);
        return $this;
    }
}