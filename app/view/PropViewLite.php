<?php

namespace app\view;

use app\domain\asset\AssetUtils;
use app\domain\mall\BuyTypes;
use app\domain\mall\MallSystem;
use app\domain\prop\PropUnitWearDay;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class PropViewLite extends PropView
{

    public static function encodeProp($prop, $roomId, $timestamp)
    {
        $ret = [];
        $isDied = $prop->isDied($timestamp);
        $relateGoodsMall = MallSystem::getInstance()->findGoodsByAssetId(AssetUtils::makePropAssetId($prop->kind->kindId));
        $ret['isOverdue'] = $isDied ? 0 : 1;        //是否过期了
        $ret['endTimeDay'] = self::calcPropEndTimeStr($prop, $timestamp);   //到期时间
        $ret['propId'] = $prop->propId;     // 道具id ，续费时使用该参数
        $ret['isWare'] = $prop->isWore;        //是否佩戴
        $ret['isBuy'] = $relateGoodsMall ? ($relateGoodsMall[0]->buyType == BuyTypes::$BUY ? 1 : 0) : 0;   //是否购买
        $ret['kindId'] = $prop->kind->kindId;    //道具类型id
        $ret['typeName']=$prop->kind->getTypeName();
        return $ret;
    }
}