<?php

namespace app\view;

use app\domain\asset\AssetUtils;
use app\domain\mall\MallSystem;
use app\domain\prop\PropAction;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class PropView
{
    public static $shelvesAreaTypeMap = [
        'avatar' => ['id' => 1, 'name' => '头像框'],
        'bubble' => ['id' => 48, 'name' => '消息气泡'],
        'voiceprint' => ['id' => 101, 'name' => '麦位光圈'],
        'mount' => ['id' => 103, 'name' => '坐骑'],
        'simple' => ['id' => 104, 'name' => '道具']
    ];

    public static $pidToPropTypeName = [
        1 => 'avatar',
        48 => 'bubble',
        101 => 'voiceprint',
        103 => 'mount',
        104 => 'simple',
    ];

    public static $propTypeToAttirePidMap = [
        'avatar' => 1,
        'bubble' => 48,
        'voiceprint' => 101,
        'mount' => 103,
        'simple' => 104
    ];

    public static $propTypeToAttireTypeMap = [
        'avatar' => 1,
        'bubble' => 2,
        'voiceprint' => 3,
        'mount' => 4,
    ];

    public static function calcPropPid($prop) {
        return ArrayUtil::safeGet(self::$propTypeToAttirePidMap, $prop->kind->getTypeName());
    }

    public static function calcPropTypeByPid($pid) {
        return ArrayUtil::safeGet(self::$pidToPropTypeName, $pid);
    }

    public static function calcPropType($type) {
        return ArrayUtil::safeGet(self::$propTypeToAttirePidMap, $type);
    }

    public static function calcAttireTypeByProp($prop) {
        if (array_key_exists($prop->kind->getTypeName(), self::$propTypeToAttireTypeMap)) {
            return self::$propTypeToAttireTypeMap[$prop->kind->getTypeName()];
        }
        return 1;
    }

    public static function calcPropEndTimeStr($prop, $timestamp) {
        // 是否过期
        if ($prop != null) {
            return $prop->kind->unit->buildDisplay($prop, $timestamp);
        }
        return '';
    }

    public static function encodeProp($prop, $roomId, $timestamp) {
        $ret = [];
        $isDied = $prop->isDied($timestamp);
        $relateGoodsMall = MallSystem::getInstance()->findGoodsByAssetId(AssetUtils::makePropAssetId($prop->kind->kindId));
        $ret['attire_type'] = self::calcAttireTypeByProp($prop);
        $ret['attire_image'] = CommonUtil::buildImageUrl($prop->kind->image);
        $ret['isOverdue'] = $isDied ? 0 : 1;
        $ret['endtime'] = self::calcPropEndTimeStr($prop, $timestamp);
        $ret['id'] = $prop->propId;
        $ret['attid'] = $prop->kind->kindId;
        $ret['is_ware'] = $isDied ? 0 : $prop->isWore;
        $ret['attire_name'] = $prop->kind->name;
        $ret['attire_describe'] = $prop->kind->desc;
        $ret['roomId'] = $roomId;
        $ret['attire_price'] = $relateGoodsMall ? MallView::encodePriceList($relateGoodsMall[0]) : MallView::encodePriceList(null);
        $ret['is_buy'] = $relateGoodsMall ? ($relateGoodsMall[0]->isBuy() ? 1 : 0) : 0;
        $ret['get_type'] = $relateGoodsMall ? MallView::calcGetType($relateGoodsMall[0], $relateGoodsMall[1]) : 0;
        $ret['is_vip'] = $relateGoodsMall ? MallView::calcIsVipGoods($relateGoodsMall[0]) : 0;
        $ret['activity_url'] = $relateGoodsMall ? $relateGoodsMall[0]->activityUrl : '';
        $ret['pid'] = self::calcPropPid($prop);
        $ret['bubble_word_image'] = CommonUtil::buildImageUrl($prop->kind->bubbleWordImage);
        $ret['multiple'] = $prop->kind->multiple;
        $ret['actions'] = self::actionViewArr($prop->kind->getCanUseAction($prop, $timestamp));
        return $ret;
    }

    public static function encodeNewProp($prop, $timestamp) {
        $breakupAction = $prop->kind->getBreakupAction();
        return [
            'kindId' => $prop->kind->kindId,
            'propName' => $prop->kind->name,
            'propImage' => CommonUtil::buildImageUrl($prop->kind->image),
            'propAnimation' => CommonUtil::buildImageUrl($prop->kind->animation),
            'propBubbleImage' => CommonUtil::buildImageUrl($prop->kind->bubbleWordImage),
            'endTimeStr' => self::calcPropEndTimeStr($prop, $timestamp),
            'isWare' => $prop->isWore,
            'multiple' => $prop->kind->multiple,
            'breakupDay' => empty($breakupAction) ? 0 : $prop->breakUpBalance($timestamp), //可分解的天数
            'breakupAssets' => empty($breakupAction) ? [] : $breakupAction->getAssets($prop, $timestamp),
        ];
    }

    public static function actionViewArr($propActionArr){
        $result=[];
        foreach($propActionArr as $propAction){
            $result[]=self::actionView($propAction);
        }
        return $result;
    }

    public static function actionView(PropAction $propAction){
        $result['name']=$propAction->name;
        $result['displayName']=$propAction->displayName;
        return $result;
    }


    public static function authPropType($propType) {
        if (!in_array($propType,self::$pidToPropTypeName)){
            return false;
        }
        return true;
    }
}