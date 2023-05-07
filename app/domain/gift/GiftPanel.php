<?php


namespace app\domain\gift;


use app\domain\activity\weekStar\ZhouXingService;
use app\utils\ArrayUtil;

class GiftPanel
{
    public $name = '';
    public $displayName = '';
    public $giftIds = null;
    public $gifts = null;

    public function decodeFromJson($jsonObj) {
        $this->name = $jsonObj['name'];
        $this->displayName = $jsonObj['displayName'];
//        if($this->name == 'gift'){
//            $weeStarGiftId = ZhouXingService::getInstance()->getWeekGiftId(); //获取周星礼物ID
//            if($weeStarGiftId && !in_array($weeStarGiftId,$jsonObj['gifts'])){
//                array_unshift($jsonObj['gifts'],$weeStarGiftId);
//            }
//        }
        $this->giftIds = $jsonObj['gifts'];
    }

    public function initByGiftMap($giftMap) {
        $this->gifts = [];
        foreach ($this->giftIds as $giftId) {
            $gift = ArrayUtil::safeGet($giftMap, $giftId);
            if ($gift != null) {
                $this->gifts[] = $gift;
            }
        }
    }
}