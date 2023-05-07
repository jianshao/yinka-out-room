<?php


namespace app\domain\game\box;


use app\domain\asset\AssetItem;
use app\domain\gift\GiftSystem;
use app\domain\prop\PropSystem;
use app\utils\ArrayUtil;
use think\facade\Log;

class Box
{
    public $boxId = '';
    public $price = null;
    public $hammerPropId = null;
    public $personalSpecialGifts = null;
    public $personalSpecialGiftValue = 0;
    public $globalSpecialGifts = null;
    public $globalSpecialGiftValue = 0;
    public $giftWeightList = null;
    public $totalWeight = 0;
    public $avatarKind = null;
    // 个人进度最大值
    public $maxPersonalProgress = 0;
    // 全局进度最大值
    public $maxGlobalProgress = 0;
    // 个人进度满以后获得特殊礼物的总权重
    public $personalProgressFullTotalWeight = 0;
    // 全局进度满以后获得特殊礼物的总权重
    public $globalProgressFullTotalWeight = 0;

    public $maxPool = 0;
    public $maxPoolFull = 0;

    public function decodeFromJson($jsonObj) {
        $this->boxId = ArrayUtil::safeGet($jsonObj, 'boxId');

        $price = new AssetItem();
        $price->decodeFromJson($jsonObj['price']);
        $this->price = $price;
        $this->hammerPropId = $jsonObj['hammerPropId'];
        $this->personalSpecialGifts = [];
        $personalSpecialGifts = ArrayUtil::safeGet($jsonObj, 'personalSpecialGifts', []);
        foreach ($personalSpecialGifts as $giftId) {
            $gift = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($gift) {
                $this->personalSpecialGifts[] = $gift;
            } else {
                Log::error(sprintf('Box::decodeFromJson UnknownPersonalSpecialGift $giftId=%d', $giftId));
            }
        }

        $this->globalSpecialGifts = [];
        $globalSpecialGifts = ArrayUtil::safeGet($jsonObj, 'globalSpecialGifts', []);
        foreach ($globalSpecialGifts as $giftId) {
            $gift = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($gift) {
                $this->globalSpecialGifts[] = $gift;
            } else {
                Log::error(sprintf('Box::decodeFromJson UnknownGlobalSpecialGift $giftId=%d', $giftId));
            }
        }

        $this->giftWeightList = [];
        $this->totalWeight = 0;
        $randomGifts = ArrayUtil::safeGet($jsonObj, 'gifts', []);
        foreach ($randomGifts as $randomGift) {
            $giftId = $randomGift['giftId'];
            $weight = $randomGift['weight'];
            $gift = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($gift) {
                $this->totalWeight += $weight;
                $this->giftWeightList[] = [$gift, $this->totalWeight, $weight];
            } else {
                Log::error(sprintf('Box::decodeFromJson UnknownGift $giftId=%d', $giftId));
            }
        }

        $avatarKindId = ArrayUtil::safeGet($jsonObj, 'avatarKindId');
        if ($avatarKindId != null) {
            $this->avatarKind = PropSystem::getInstance()->findPropKind($avatarKindId);
        }

        $this->maxPersonalProgress = ArrayUtil::safeGet($jsonObj, 'maxPersonalProgress', 0);
        $this->maxGlobalProgress = ArrayUtil::safeGet($jsonObj, 'maxGlobalProgress', 0);
    }
}