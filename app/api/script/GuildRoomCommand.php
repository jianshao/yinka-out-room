<?php

namespace app\api\script;

use app\domain\guild\cache\GuildRoomBucket;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomTypeModelDao;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  公会房间数据清洗更新 维护公会热度backet 更新公会数据缓存
 * Class GuildRoomCommand
 * @package app\command
 * @command  php think GuildRoomCommand 60  >> /tmp/GuildRoomCommand.log 2>&1
 */
class GuildRoomCommand extends RoomBaseCommond
{

    protected $roomType = 0;
    protected $zeroType = 60;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\GuildRoomCommand')
//            ->addArgument('roomType', Argument::OPTIONAL, "roomType int [25 26 24 60]")
            ->setDescription('refresh GuildRoomCommand sort bucket data');
    }


    private function initTypeIds()
    {
        $result = RoomTypeModelDao::getInstance()->getGuildTypeIds();
        $result[] = $this->zeroType;
        return array_values($result);
    }


//    protected function execute(Input $input, Output $output)
//    {
//        $this->roomType = $input->getArgument('roomType') ? intval($input->getArgument('roomType')) : $this->zeroType;
//        $output->writeln(sprintf('app\command\GuildRoomCommand entry err date:%s roomType:%d', $this->getDateTime(), $this->roomType));
//        try {
//            list($refreshNumber, $listCount) = $this->fitExeCute();
//        } catch (\Exception $e) {
//            $output->writeln(sprintf("execute date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
//            return;
//        }
//        // 指令输出
//        $output->writeln(sprintf('app\command\GuildRoomCommand success end date:%s exec refreshNumber:%d dataNumber:%d roomType:%d', $this->getDateTime(), $refreshNumber, $listCount, $this->roomType));
//    }


    protected function execute(Input $input, Output $output)
    {
        $output->writeln(sprintf('app\command\GuildRoomCommand execute entry date:%s', $this->getDateTime()));
        $typeIds = $this->initTypeIds();
        if (empty($typeIds)) {
            $output->writeln(sprintf('app\command\GuildRoomCommand init typeIds date:%s fatal error not find person type ids', $this->getDateTime()));
        }
        foreach ($typeIds as $typeId) {
            $this->roomType = $typeId;
            $output->writeln(sprintf('app\command\GuildRoomCommand entry date:%s roomType:%d', $this->getDateTime(), $this->roomType));
            try {
                list($refreshNumber, $listCount) = $this->fitExeCute($output);
            } catch (\Exception $e) {
                $output->writeln(sprintf("app\command\GuildRoomCommand execute exception date:%s typeId:%s error:%s error trice:%s", $this->getDateTime(), $typeId, $e->getMessage(), $e->getTraceAsString()));
                continue;
            }
            $output->writeln(sprintf('app\command\GuildRoomCommand success end date:%s exec refreshNumber:%d dataNumber:%d roomType:%d', $this->getDateTime(), $refreshNumber, $listCount, $this->roomType));
            usleep(1000000);
        }
        // 指令输出
        $output->writeln(sprintf('app\command\GuildRoomCommand execute success end date:%s', $this->getDateTime()));
    }


    /**
     * @return array [刷新后zset的总数，count list的总数]
     */
    private function fitExeCute(Output $output)
    {
//        获取所有公会房间id
        $guildIds = $this->getGuildids();
        $output->writeln(sprintf('app\command\GuildRoomCommand entry date:%s guildIds:%s', $this->getDateTime(), json_encode($guildIds)));
//        根据房间id查询手动热度值，用户热度值，礼物热度值，聊天热度值,重新计算房间热度值，排序，
        $roomModelList = $this->roomIdToData($guildIds);
        $output->writeln(sprintf('app\command\GuildRoomCommand entry date:%s roomModelList:%s', $this->getDateTime(), json_encode($roomModelList)));

//        缓存公会房间明细数据
        $this->cacheData($guildIds, $roomModelList);
//        拆分：锁房的，没锁房的
        list($lockRoomModelList, $unLockRoomModelList) = $this->splitLockRoom($roomModelList);
        $unLockRoomModelList = $this->sortData($unLockRoomModelList);
        $lockRoomModelList = $this->sortData($lockRoomModelList);
//        重组zset数据
        $resetList = $this->reset($lockRoomModelList, $unLockRoomModelList);
//        更新热度值zset
        $output->writeln(sprintf('app\command\GuildRoomCommand entry date:%s resetList:%s', $this->getDateTime(), json_encode($resetList)));
        $refreshNumber = $this->refreshBucket($resetList);
        return [$refreshNumber, count($resetList)];
    }

    /**
     * @param $resetList
     * @return int  set的总数
     */
    private function refreshBucket($resetList)
    {
        $bucketObj = new GuildRoomBucket($this->roomType);
        return $bucketObj->storeList($resetList);
    }

//    获取guild公会id数据
    private function getGuildids()
    {
        return RoomModelDao::getInstance()->getOnlineGuildRoomIdsForRoomType($this->roomType);
    }

}
