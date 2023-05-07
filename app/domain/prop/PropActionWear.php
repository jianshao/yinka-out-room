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
class PropActionWear extends PropAction
{
    public static $TYPE_NAME = 'wear';

    public function doAction($propBag, $prop, $action, $actionParams, $timestamp) {
        if ($prop->isWore == 1) {
            throw new FQException('您已经穿戴此装扮', 500);
        }
        if ($prop->isDied($timestamp)) {
            throw new FQException('装扮已经过期', 500);
        }
        // 先取消佩戴的本类型的装扮
        $beforeArr = $this->unwearAllByType($propBag, $prop->kind->getTypeName(), $timestamp);
        $currArr = $this->wear($propBag, $prop, $timestamp);
        return [array_merge($beforeArr, $currArr), null, 0];
    }

    private function wear($propBag, $prop, $timestamp) {
        $prop->isWore = 1;
        // 需要单位进行处理
        $prop->kind->unit->processWear($prop, $timestamp);
        $propBag->updateProp($prop);

        Log::info(sprintf('WearAttire userId=%d kindId=%d type=%d propId=%d',
            $propBag->getUserId(), $prop->kind->kindId,
            $prop->kind->getTypeName(), $prop->propId));
        return [$prop];
        // TODO event
    }

    private function unwearAllByType($propBag, $typeName, $timestamp) {
        $arr = [];
        foreach ($propBag->getPropMap() as $propId => $prop) {
            if ($prop->kind instanceof PropKindAttire
                && $prop->kind->getTypeName() == $typeName
                && $prop->isWore) {
                $arr[] = $this->unwear($propBag, $prop, $timestamp);
            }
        }
        return $arr;
    }

    private function unwear($propBag, $prop, $timestamp) {
        assert($prop->isWore == 1);
        $prop->isWore = 0;
        $prop->updateTime = $timestamp;

        // 需要单位进行处理
        $prop->kind->unit->processUnwear($prop, $timestamp);
//        $prop->woreTime = $timestamp;
        $propBag->updateProp($prop);

        Log::info(sprintf('UnwearAttire userId=%d kindId=%d type=%s propId=%d',
            $propBag->getUserId(), $prop->kind->kindId,
            $prop->kind->getTypeName(), $prop->propId));
        return $prop;
        // TODO event
    }

    public function canUseAction($prop, $timestamp) {
        if (!$prop->isWore) {
            return true;
        }
        return false;
    }
}


