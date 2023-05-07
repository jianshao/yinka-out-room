<?php

namespace app\domain\prop\service;

use app\core\mysql\Sharding;
use app\domain\user\UserRepository;
use app\domain\exceptions\FQException;
use app\event\PropTypeActionEvent;
use think\facade\Log;
use Exception;

/**
 * 道具接口，目前主要是装扮
 */
class PropService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PropService();
        }
        return self::$instance;
    }

    /**
     * 获取用户道具背包
     */
    public function getPropBag($userId) {
        Log::debug(sprintf('>>> PropService::getPropBag userId=%d', $userId));

        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $timestamp = time();
                return  $user->getAssets()->getPropBag($timestamp);
            });
        } catch (Exception $e) {
            Log::error(sprintf('PropService::getPropBag userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }

    /**
     * 根据道具ID执行动作
     * 
     * @param userId: 哪个用户
     * @param propId: 哪个道具
     * @param action: 动作名称
     * @param actionParams: 动作相关参数
     */
    public function doActionByPropId($userId, $propId, $action, $actionParams) {
        Log::debug(sprintf('>>> PropService::doActionByPropId userId=%d propId=%s action=%s',
            $userId, $propId, $action));
        try {
            list($typeName, $timestamp, $prop) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use(
                $userId, $propId, $actionParams, $action
            ) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $timestamp = time();
                $propBag = $user->getAssets()->getPropBag($timestamp);
                $prop = $propBag->doActionByPropId($propId, $action, $actionParams);
                $typeName = $prop->kind->getTypeName();
                return [$typeName, $timestamp, $prop];
            });
        } catch (Exception $e) {
            Log::error(sprintf('PropService::doActionByPropId Exception userId=%d propId=%s action=%s ex=%d:%s',
                $userId, $propId, $action, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        event(new PropTypeActionEvent($userId, $typeName, $action, $actionParams, $timestamp));
        return $prop;
    }

    /**
     * 对某一类型的道具执行动作，比如头像框或者气泡框
     *
     * @param $userId
     * @param $typeName
     * @param $action
     * @param $actionParams
     */
    public function doActionByPropType($userId, $typeName, $action, $actionParams) {
        Log::debug(sprintf('>>> PropService::doActionByPropType userId=%d typeName=%s action=%s',
            $userId, $typeName, $action));
        $timestamp = time();
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use($userId, $typeName, $action, $actionParams, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $propBag = $user->getAssets()->getPropBag($timestamp);
                $propBag->doActionByPropType($typeName, $action, $actionParams);
            });
        } catch (Exception $e) {
            Log::error(sprintf('PropService::doActionByPropType Exception userId=%d typeName=%s action=%s ex=%d:%s',
                $userId, $typeName, $action, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        event(new PropTypeActionEvent($userId, $typeName, $action, $actionParams, $timestamp));
    }

    public function doActionByKindId($userId, $kindId, $action, $actionParams) {
        Log::debug(sprintf('>>> PropService::doActionByKindId userId=%d kindId=%s action=%s',
            $userId, $kindId, $action));
        $timestamp = time();
        try {
            list($typeName, $props, $assetList, $count) = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use(
                $userId, $kindId, $action, $actionParams, $timestamp
            ) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $propBag = $user->getAssets()->getPropBag($timestamp);
                list($prop, $props, $assetList, $count) = $propBag->doActionByPropKind($kindId, $action, $actionParams);
                $typeName = $prop->kind->getTypeName();
                Log::info(sprintf('PropService::doActionByKindId userId=%d kindId=%s action=%s assetList=%s assetList=%s count=%d',
                    $userId, $kindId, $action, json_encode($props), json_encode($assetList), $count));
                return [$typeName, $props, $assetList, $count];
            });
        } catch (Exception $e) {
            Log::error(sprintf('PropService::doActionByPropType Exception userId=%d kindId=%s action=%s ex=%d:%s',
                $userId, $kindId, $action, $e->getCode(), $e->getMessage()));
            throw $e;
        }
        event(new PropTypeActionEvent($userId, $typeName, $action, $actionParams, $timestamp));
        return [$props, $assetList, $count];
    }


    public function doActionByKindIds($userId, $kindIds, $action, $actionParams) {
        $timestamp = time();
        try {
            $totalAssetList = Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use(
                $userId, $kindIds, $actionParams, $action, $timestamp
            ){
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $totalAssetList = [];
                foreach ($kindIds as $kindId){
                    $propBag = $user->getAssets()->getPropBag($timestamp);
                    list($prop, $props, $assetList, $count) = $propBag->doActionByPropKind($kindId, $action, $actionParams);
                    if (!empty($assetList)) {
                        $totalAssetList[] = [$assetList, $count];
                    }
                }
                Log::info(sprintf('PropService::doActionByKindIds userId=%d action=%s kindIds=%s totalAssetList=%s',
                    $userId, $action, json_encode($kindIds), json_encode($totalAssetList)));
                return $totalAssetList;
            });
        } catch (Exception $e) {
            Log::error(sprintf('PropService::doActionByPropType Exception userId=%d action=%s kindIds=%s ex=%d:%s',
                $userId, $action, json_encode($kindIds), $e->getCode(), $e->getMessage()));
            throw $e;
        }

        return $totalAssetList;
    }
}