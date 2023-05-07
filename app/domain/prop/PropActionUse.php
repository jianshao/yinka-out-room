<?php

namespace app\domain\prop;
use app\domain\asset\AssetItem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 装扮类道具
 */
class PropActionUse extends PropAction
{
    public static $TYPE_NAME = 'use';
    public $assetItemList = [];

    public function decodeFromJson($jsonObj) {
        parent::decodeFromJson($jsonObj);
        $sendAssetList = ArrayUtil::safeGet($jsonObj, 'sendAssets');
        if ($sendAssetList) {
            $this->assetItemList = AssetItem::decodeList($sendAssetList);
        }
    }

    public function doAction($propBag, $prop, $action, $actionParams, $timestamp) {
        $user = $propBag->getUser();
        $userAssets = $user->getAssets();
        $biEvent = BIReport::getInstance()->makePropActionBIEvent($prop->kind->kindId, 1, self::$TYPE_NAME);
        $propBag->consume($prop, 1, $timestamp, $biEvent);
        //发货
        foreach ($this->assetItemList as $assetItem) {
            $userAssets->add($assetItem->assetId, $assetItem->count, $timestamp, $biEvent);
        }
        return [[$prop], $this->assetItemList, 1];
    }

    public function canUseAction($prop, $timestamp) {
        return true;
    }
}


