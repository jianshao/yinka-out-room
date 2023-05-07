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
class PropActionUnWear extends PropAction
{
    public static $TYPE_NAME = 'unwear';

    public function doAction($propBag, $prop, $action, $actionParams, $timestamp)
    {
        if ($prop->isWore != 1) {
            throw new FQException('您没有穿戴此装扮', 500);
        }
        assert($prop->isWore == 1);
        $prop->isWore = 0;
        $prop->updateTime = $timestamp;

        // 需要单位进行处理
        $prop->kind->unit->processUnwear($prop, $timestamp);
        $propBag->updateProp($prop);

        Log::info(sprintf('UnwearAttire userId=%d kindId=%d type=%s propId=%d',
            $propBag->getUserId(), $prop->kind->kindId,
            $prop->kind->getTypeName(), $prop->propId));
        return [[$prop], null, 0];
        // TODO event
    }

    public function canUseAction($prop, $timestamp)
    {
        if ($prop->isWore) {
            return true;
        }
        return false;
    }
}


