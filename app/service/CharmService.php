<?php

namespace app\service;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use Exception;
use think\facade\Log;

class CharmService
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new CharmService();
        }
        return self::$instance;
    }

    public function clearCharm($roomId, $micIds) {
        if (!is_integer($roomId) || $roomId <= 0) {
            throw new FQException('房间Id参数错误', 500);
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $heartValueList = [];
        foreach ($micIds as $micId) {
            $redis->hset('micCharm_'.$roomId, $micId, 0);
            if (is_integer($micId) && is_integer($micId) > 0) {
                $heartValueList[] = [
                    'micId' => $micId,
                    'HeartValue' => 0
                ];
            }
        }
        try {
            //发消息操作
            if(count($heartValueList) > 0){
                $res = RoomNotifyService::getInstance()->notifyRoomCharmUpdate($roomId, $heartValueList);
                Log::info(sprintf('CharmService::clearCharm roomId=%d micIds=%s resp=%s',
                    $roomId, implode(',', $micIds), $res));
            }
        } catch (FQException $e) {
            Log::error(sprintf('CharmService::clearCharm roomId=%d micIds=%s ex=%d:%s',
                $roomId, implode(',', $micIds), $e->getCode(), $e->getMessage()));
            throw new FQException($e->getMessage(), $e->getCode());
        }
    }

    public function addCharm($roomId, $charmDatas) {
        if (!is_integer($roomId) || $roomId <= 0) {
            throw new FQException('房间Id参数错误', 500);
        }
        try {
            //发消息操作
            $redis = RedisCommon::getInstance()->getRedis();
            $heartValueList = [];
            foreach ($charmDatas as $micId => $charm) {
                $heartValue = $redis->HINCRBY('micCharm_'.$roomId, $micId, $charm);
                if (is_integer($micId) && is_integer($micId) > 0) {
                    $heartValueList[] = [
                        'micId' => $micId,
                        'HeartValue' => $heartValue
                    ];
                }
            }

            if(count($heartValueList) > 0){
                $res_charm = RoomNotifyService::getInstance()->notifyRoomCharmUpdate((int)$roomId, $heartValueList);
                Log::info(sprintf('CharmService::addCharm roomId=%d msg=%s resp=%s',
                    $roomId, json_encode($heartValueList), $res_charm));
            }
        } catch (FQException $e) {
            Log::error(sprintf('CharmService::addCharm roomId=%d msg=%s ex=%d:%s',
                $roomId, json_encode($heartValueList), $e->getCode(), $e->getMessage()));
            throw new FQException($e->getMessage(), $e->getCode());
        }
    }


    public function makeCharmList($micIds)
    {
        $result = [];
        foreach ($micIds as $micId) {
            $result[] = $this->makeCharm($micId);
        }
        return $result;
    }

    private function makeCharm($micId)
    {
        return [
            'micId' => $micId,
            'visitor' => 0
        ];
    }

}