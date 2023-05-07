<?php

namespace app\domain\gift;
use app\domain\asset\AssetItem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 装扮类道具
 */
class GiftActionBreakup extends GiftAction
{
    public static $TYPE_NAME = 'breakup';
    public $assetItemList = [];

    public function decodeFromJson($jsonObj) {
        parent::decodeFromJson($jsonObj);
        $assetList = ArrayUtil::safeGet($jsonObj, 'sendAssets');
        if ($assetList) {
            $this->assetItemList = AssetItem::decodeList($assetList);
        }
    }

    public function doAction($giftBag, $giftKind, $count, $actionParams, $timestamp) {
        $user = $giftBag->getUser();
        $userAssets = $user->getAssets();
        $biEvent = BIReport::getInstance()->makeGiftActionBIEvent($giftKind->kindId, $count, self::$TYPE_NAME);
        $giftBag->consume($giftKind->kindId, $count, $timestamp, $biEvent);
        //发货
        foreach ($this->assetItemList as $assetItem) {
            $userAssets->add($assetItem->assetId, $assetItem->count*$count, $timestamp, $biEvent);
        }

        return $this->assetItemList;
    }

    public function canUseAction($gift, $timestamp) {
        return $gift->getCount($timestamp) > 0;
    }
}


