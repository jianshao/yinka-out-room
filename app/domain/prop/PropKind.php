<?php

namespace app\domain\prop;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

/**
 * 道具种类
 */
abstract class PropKind
{
    public static $TYPE_NAME = '';

    // 道具种类ID
    public $kindId = 0;
    // 道具单位 PropUnit
    public $unit = null;
    // 道具名称
    public $name = '';
    // 道具说明
    public $desc = '';
    // 图片
    public $image = '';
    // Android用的图片
    public $imageAndroid = '';
    // 道具终结时是否从背包删除
    public $removeFormBagWhenDied = 0;
    // 是否在背包展示
    public $showInBag = true;
    public $color = '';
    public $multiple = '';
    public $animation = '';
    public $bubbleWordImage = '';
    public $type = '';
    public $goodsId = '';
    public $actionMap = [];
    public $textColor = '';  // 文字颜色

    public function decodeFromJson($jsonObj) {
        $this->kindId = $jsonObj['kindId'];
        $this->unit = PropUnitRegister::getInstance()->decodeFromJson($jsonObj['unit']);
        $this->name = $jsonObj['name'];
        $this->desc = $jsonObj['desc'];
        $this->image = $jsonObj['image'];
        $this->imageAndroid = $jsonObj['imageAndroid'];
        $this->removeFormBagWhenDied = ArrayUtil::safeGet($jsonObj, 'removeFormBagWhenDied', false);
        if (ArrayUtil::safeGet($jsonObj, 'showInBag', 1) === 0) {
            $this->showInBag = false;
        }
        $this->color = ArrayUtil::safeGet($jsonObj, 'color', '');
        $this->multiple = ArrayUtil::safeGet($jsonObj, 'multiple', '');
        $this->animation = ArrayUtil::safeGet($jsonObj, 'animation', '');
        $this->bubbleWordImage = ArrayUtil::safeGet($jsonObj, 'bubbleWordImage', '');
        $this->type = ArrayUtil::safeGet($jsonObj, 'type', "");
        $this->textColor = ArrayUtil::safeGet($jsonObj, 'textColor', "");
        if (ArrayUtil::safeGet($jsonObj,'actions')) {
            $this->actionMap = PropActionRegister::getInstance()->encodeList($jsonObj['actions']);
        }
        $this->decodeFromJsonImpl($jsonObj);
    }

    public function getCanUseAction($prop, $timestamp) {
        $ret = [];
        foreach ($this->actionMap as $map) {
            if($map->canUseAction($prop, $timestamp)) {
                $ret[] = $map;
            }
        }
        return $ret;
    }

    public function getBreakupAction() {
        return ArrayUtil::safeGet($this->actionMap, PropActionBreakup::$TYPE_NAME);
    }

    // 执行动作
    public function doAction($propBag, $prop, $actionName, $actionParams, $timestamp) {
        throw new FQException('不支持的操作类型', 500);
    }

    public function getTypeName() {
        assert(0, 'Not implement');
    }

    // 产生一个新道具
    public abstract function newProp($propId);

    public function processWhenDied($propBag, $prop, $timestamp) {

    }

    //
    protected function decodeFromJsonImpl($jsonObj) {

    }


}


