<?php

namespace app\domain\prop;
use app\domain\asset\AssetItem;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;

/**
 * 道具使用方法
 */
abstract class PropAction
{
    public static $TYPE_NAME = '';
    public $name = '';
    public $displayName = '';

    public function __construct($name = '', $displayName = '')
    {
        $this->name = $name;
        $this->displayName = $displayName;
    }

    /**
     *  道具具体操作方式
     * @param prop: 道具
     * @param count: 数量
     * @param timestamp: 增加时的时间戳
     */
    abstract public function doAction($propBag, $prop, $action, $actionParams, $timestamp);

    public function decodeFromJson($jsonObj) {
        $this->name = ArrayUtil::safeGet($jsonObj, 'name');
        $this->displayName = ArrayUtil::safeGet($jsonObj, 'displayName');
    }

    abstract public function canUseAction($prop, $timestamp);
}


