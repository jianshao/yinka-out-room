<?php

namespace app\api\script;


use app\domain\guild\cache\GuildRoomCache;
use app\domain\guild\cache\HomeHotRoomCache;
use app\domain\guild\cache\RecreationHotRoomCache;
use app\query\room\cache\GuildQueryRoomModelCache;
use app\query\room\model\QueryRoom;
use app\query\room\service\QueryRoomService;
use think\console\Command;
use think\console\input\Argument;


ini_set('set_time_limit', 0);

/**
 * @info  mua房间推荐位的room数据
 *        mua只排序，不做roomdata缓存
 * Class MuaRoomDataRefreshCommand
 * @package app\command
 * @command  php think MuaRoomDataRefreshCommand 9999>> /tmp/MuaRoomDataRefreshCommand.log 2>&1
 */
class RoomBaseCommond extends Command
{

    protected $roomType = 0;
    protected $zeroType = 0;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\MuaRoomDataRefreshCommand')
            ->addArgument('roomType', Argument::OPTIONAL, "roomType int [9999 ]")
            ->setDescription('refresh MuaRoomDataRefreshCommand sort bucket data');
    }

    protected function getUnixTime()
    {
        return time();
    }

    protected function getDateTime()
    {
        return date("Y-m-d H:i:s", $this->getUnixTime());
    }


    /**
     * @info 初始化房间热度值总数
     * @param $roomModelList
     * @param $itemRoomModel
     * @return int
     */
    protected function initHotRoomLock($roomModelList, $itemRoomModel)
    {
        if (isset($roomModelList[$itemRoomModel->roomId]) === false) {
            return 0;
        }
        return $roomModelList[$itemRoomModel->roomId]->getLock();
    }

    /**
     * @info 初始化房间热度值总数
     * @param $roomModelList
     * @param $itemRoomModel
     * @return int
     */
    protected function initHotRoomSum($roomModelList, $itemRoomModel)
    {
        if (isset($roomModelList[$itemRoomModel->roomId]) === false) {
            return 0;
        }
        return $roomModelList[$itemRoomModel->roomId]->getHotSumTpl();
    }

    protected function roomIdToData($guildIds)
    {
        $result = [];
        foreach ($guildIds as $guildId) {
            $guildRoomCache = $this->getItemRoomHotData($guildId);
            if ($guildRoomCache->isEmptyRoom()) {
                continue;
            }
            $result[$guildId] = $guildRoomCache;
        }
        return $result;
    }

    protected function getItemRoomHotData($guildId)
    {
        $model = new GuildRoomCache($guildId);
        $model->initLockRoom();
        return $model;
    }

    protected function getItemHomeHotData($guildId)
    {
        $model = new HomeHotRoomCache($guildId);
        $model->initLockRoom();
        return $model;
    }

    protected function getItemRecreationHotData($guildId)
    {
        $model = new RecreationHotRoomCache($guildId);
        $model->initLockRoom();
        return $model;
    }


    /**
     * @info 缓存数据 type不为0 则不缓存
     * @param $guildIds
     * @param $roomModelList
     */
    protected function cacheData($guildIds, $roomModelList)
    {
        if ($this->roomType != $this->zeroType) {
            return;
        }
        $generator = $this->readRoomInfo($guildIds);
        foreach ($generator as $k => $itemRoomModel) {
            if (empty($itemRoomModel)) {
                continue;
            }
//            join param
            $this->joinParam($itemRoomModel);
//            拼接热度值sum总数 QueryRoomModelCache
            $itemRoomModel->visitorNumber = $this->initHotRoomSum($roomModelList, $itemRoomModel);
            $itemRoomModel->lock = $this->initHotRoomLock($roomModelList, $itemRoomModel);
            GuildQueryRoomModelCache::getInstance()->store($itemRoomModel->roomId, $itemRoomModel);
        }
        return;
    }

    protected function readRoomInfo($ids)
    {
        foreach ($ids as $roomId) {
//            $d = QueryRoomModelDao::getInstance()->queryRoomsImplSecond($roomId);
            $d = QueryRoomService::getInstance()->queryRoomsImpl($roomId);
            yield $d;
        }
    }

    protected function joinParam(QueryRoom $itemRoomModel)
    {
        $itemRoomModel->online = GuildQueryRoomModelCache::getInstance()->getOnlineRoomUserCount($itemRoomModel->roomId);
        return;
    }


    protected function sortData($data)
    {
        usort($data, function ($object1, $object2) {
            return $object1->getHotSum() < $object2->getHotSum();
        });
        return $data;
    }


    /**
     * @Info 锁房和没锁房的房间数据排序
     * @param $lockRoomModelList
     * @param $unLockRoomModelList
     * @return mixed
     */
    protected function reset($lockRoomModelList, $unLockRoomModelList)
    {
        foreach ($lockRoomModelList as $itemLockRoom) {
            $unLockRoomModelList[] = $itemLockRoom;
        }
        return $unLockRoomModelList;
    }


    /**
     * @Info 拆分锁房没锁房的房间数据
     * @param $roomModelList
     * @return array[]
     */
    protected function splitLockRoom($roomModelList)
    {
        $lockRoomModelList = [];
        $unLockRoomModelList = [];
        foreach ($roomModelList as $roomModel) {
            if ($roomModel->getLock() === true) {
                $lockRoomModelList[] = $roomModel;
            } else {
                $unLockRoomModelList[] = $roomModel;
            }
        }
        return [$lockRoomModelList, $unLockRoomModelList];
    }

    /**
     * @Info 拆分锁房没锁房的房间数据
     * @param $roomModelList
     * @return array[]
     */
    protected function trimLockRoom($roomModelList)
    {
        $unLockRoomModelList = [];
        foreach ($roomModelList as $roomModel) {
            if ($roomModel->getLock() === true) {
                continue;
            }
            $unLockRoomModelList[] = $roomModel;
        }
        return $unLockRoomModelList;
    }
}
