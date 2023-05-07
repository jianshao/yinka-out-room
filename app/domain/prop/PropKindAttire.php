<?php

namespace app\domain\prop;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 装扮类道具
 */
class PropKindAttire extends PropKind
{
    public function decodeFromJsonImpl($jsonObj)
    {
        if (!array_key_exists('wear', $this->actionMap) ) {
            $this->actionMap['wear'] = new PropActionWear('wear', '装扮');
        }
        if (!array_key_exists('unwear', $this->actionMap) ) {
            $this->actionMap['unwear'] = new PropActionUnWear('unwear', '卸下');
        }
    }

    public function newProp($propId) {
        return new PropAttire($this, $propId);
    }

    private function wear($propBag, $prop, $timestamp) {
        $prop->isWore = 1;
        // 需要单位进行处理
        $prop->kind->unit->processWear($prop, $timestamp);
        $propBag->updateProp($prop);

        Log::info(sprintf('WearAttire userId=%d kindId=%d type=%d propId=%d',
            $propBag->getUserId(), $prop->kind->kindId,
            $prop->kind->getTypeName(), $prop->propId));
        // TODO event
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

        // TODO event
    }

    private function unwearAllByType($propBag, $typeName, $timestamp) {
        foreach ($propBag->getPropMap() as $propId => $prop) {
            if ($prop->kind instanceof PropKindAttire
                && $prop->kind->getTypeName() == $typeName
                && $prop->isWore) {
                $this->unwear($propBag, $prop, $timestamp);
            }
        }
    }

    public function doAction($propBag, $prop, $action, $actionParams, $timestamp) {
        assert($prop->kind == $this);
        // 目前action比较少先这样，以后可以改成map存储
        if ($action == 'wear') {
            if ($prop->isWore == 1) {
                throw new FQException('您已经穿戴此装扮', 500);
            }
            if ($prop->isDied($timestamp)) {
                throw new FQException('装扮已经过期', 500);
            }
            // 先取消佩戴的本类型的装扮
            $this->unwearAllByType($propBag, $prop->kind->getTypeName(), $timestamp);
            $this->wear($propBag, $prop, $timestamp);
        } else if ($action == 'unwear') {
            if ($prop->isWore == 1) {
                $this->unwear($propBag, $prop, $timestamp);
            }
        } else {
            throw new FQException('此装扮不支持该操作', 500);
        }
    }

    public function processWhenDied($propBag, $prop, $timestamp) {
        if ($prop->isWore) {
            $this->unwear($propBag, $prop, $timestamp);
        }
    }
}


