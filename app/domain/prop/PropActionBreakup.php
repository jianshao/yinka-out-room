<?php

namespace app\domain\prop;
use app\domain\asset\AssetItem;
use app\domain\asset\AssetSystem;
use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 装扮类道具
 */
class PropActionBreakup extends PropAction
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

    public function doAction($propBag, $prop, $action, $actionParams, $timestamp) {
        $user = $propBag->getUser();
        $userAssets = $user->getAssets();
        $count = (int)$prop->breakUpBalance($timestamp);

        if ($count == 0) {
            throw new FQException($prop->kind->name.'没有剩余可分解天数', 500);
        }

        $biEvent = BIReport::getInstance()->makePropActionBIEvent($prop->kind->kindId, $count, self::$TYPE_NAME);
        $propBag->consume($prop, $count, $timestamp, $biEvent);
        //发货
        foreach ($this->assetItemList as $assetItem) {
            $userAssets->add($assetItem->assetId, $assetItem->count*$count, $timestamp, $biEvent);
        }
        return [[$prop], $this->assetItemList, $count];
    }

    public function canUseAction($prop, $timestamp) {
        return $prop->balance($timestamp) > 0;
    }

    public function getAssets($prop, $timestamp){
        $ret = [];
        $count = (int)$prop->breakUpBalance($timestamp);
        foreach ($this->assetItemList as $assetItem) {
            $asset = AssetSystem::getInstance()->findAssetKind($assetItem->assetId);
            $ret[] = [
                'type' => $asset->kindId,
                'displayName' => $asset->displayName,
                'count' => $assetItem->count*$count,
            ];
        }

        return $ret;
    }
}


