<?php

namespace app\api\script;

use app\domain\guild\cache\MuaRoomBucket;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomTypeModelDao;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  mua房间数据清洗更新 维护个人热度backet 更新room数据缓存
 *        mua只排序，不做roomdata缓存
 * Class MuaRoomCommand
 * @package app\command
 * @command  php think MuaRoomCommand>> /tmp/PersonRoomCommand.log 2>&1
 */
class MuaRoomCommand extends RoomBaseCommond
{

    protected $roomType = 0;
    protected $zeroType = 60;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\MuaRoomCommand')
//            ->addArgument('roomType', Argument::OPTIONAL, "roomType int [25 26 24 60]")
            ->setDescription('refresh MuaRoomCommand sort bucket data');
    }


    private function initTypeIds()
    {
        $result = RoomTypeModelDao::getInstance()->getGuildTypeIds();
        $result[] = $this->zeroType;
        return array_values($result);
    }


    protected function execute(Input $input, Output $output)
    {
        $output->writeln(sprintf('app\command\MuaRoomCommand execute entry date:%s', $this->getDateTime()));
        $typeIds = $this->initTypeIds();
        if (empty($typeIds)) {
            $output->writeln(sprintf('app\command\MuaRoomCommand init typeIds date:%s fatal error not find person type ids', $this->getDateTime()));
        }
        foreach ($typeIds as $typeId) {
            $this->roomType = $typeId;
            $output->writeln(sprintf('app\command\MuaRoomCommand entry date:%s roomType:%d', $this->getDateTime(), $this->roomType));
            try {
                list($refreshNumber, $listCount) = $this->fitExeCute($output);
            } catch (\Exception $e) {
                $output->writeln(sprintf("app\command\MuaRoomCommand execute exception date:%s typeId:%s error:%s error trice:%s", $this->getDateTime(), $typeId, $e->getMessage(), $e->getTraceAsString()));
                return;
            }
            $output->writeln(sprintf('app\command\MuaRoomCommand success end date:%s exec refreshNumber:%d dataNumber:%d roomType:%d', $this->getDateTime(), $refreshNumber, $listCount, $this->roomType));
            usleep(1000000);
        }
        // 指令输出
        $output->writeln(sprintf('app\command\MuaRoomCommand execute success end date:%s', $this->getDateTime()));
    }


//    protected function execute(Input $input, Output $output)
//    {
//        $this->roomType = $input->getArgument('roomType') ? intval($input->getArgument('roomType')) : $this->zeroType;
//        $output->writeln(sprintf('app\command\MuaRoomCommand entry err date:%s roomType:%d', $this->getDateTime(), $this->roomType));
//        try {
//            list($refreshNumber, $listCount) = $this->fitExeCute($output);
//        } catch (\Exception $e) {
//            $output->writeln(sprintf("execute date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
//            return;
//        }
//        // 指令输出
//        $output->writeln(sprintf('app\command\MuaRoomCommand success end date:%s exec refreshNumber:%d dataNumber:%d roomType:%d', $this->getDateTime(), $refreshNumber, $listCount, $this->roomType));
//    }


    /**
     * @return array [刷新后zset的总数，count list的总数]
     */
    private function fitExeCute(Output $output)
    {
//        获取所有房间id
        $guildIds = $this->getGuildids();
        $output->writeln(sprintf('app\command\MuaRoomCommand entry date:%s guildIds:%s', $this->getDateTime(), json_encode($guildIds)));
//        根据房间id查询手动热度值，用户热度值，礼物热度值，聊天热度值,重新计算房间热度值，排序，
        $roomModelList = $this->roomIdToData($guildIds);
        $output->writeln(sprintf('app\command\MuaRoomCommand entry date:%s roomModelList:%s', $this->getDateTime(), json_encode($roomModelList)));
//        拆分：锁房的，没锁房的
        list($lockRoomModelList, $unLockRoomModelList) = $this->splitLockRoom($roomModelList);
        $unLockRoomModelList = $this->sortData($unLockRoomModelList);
        $lockRoomModelList = $this->sortData($lockRoomModelList);
//        重组zset数据
        $resetList = $this->reset($lockRoomModelList, $unLockRoomModelList);
//        更新热度值zset
        $output->writeln(sprintf('app\command\MuaRoomCommand entry date:%s resetList:%s', $this->getDateTime(), json_encode($resetList)));
        $refreshNumber = $this->refreshBucket($resetList);
        return [$refreshNumber, count($resetList)];
    }

    /**
     * @param $resetList
     * @return int  set的总数
     */
    private function refreshBucket($resetList)
    {
        $bucketObj = new MuaRoomBucket($this->roomType);
        return $bucketObj->storeList($resetList);
    }

//    获取guild公会id数据
    private function getGuildids()
    {
        if ($this->roomType == $this->zeroType) {
            return $this->initDefaultGuildIds();
        }
        $result = RoomModelDao::getInstance()->getOnlineGuildRoomIdsForRoomType($this->roomType);
        return $result;
    }

    private function initDefaultGuildIds()
    {
        $data = [];
        $personRoomId = RoomModelDao::getInstance()->getOnlinePersonRoomIdsForRoomType(9999);
        $personRoomId = array_map(function ($item) {
            return intval($item);
        }, $personRoomId);
        $guildRoomId = RoomModelDao::getInstance()->getOnlineGuildRoomIdsForRoomType($this->roomType);
        $data = array_merge($data, $personRoomId);
        $data = array_merge($data, $guildRoomId);
        return $data;
    }
}
