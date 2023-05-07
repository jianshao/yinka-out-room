<?php

namespace app\api\script;

use app\domain\guild\cache\GuildRoomBucket;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomTypeModelDao;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  大厅-个人房间数据清洗更新 维护个人热度backet 更新room数据缓存
 * Class PersonRoomCommand
 * @package app\command
 * @command  php think PersonRoomCommand >> /tmp/PersonRoomCommand.log 2>&1
 */
class PersonRoomCommand extends RoomBaseCommond
{

    protected $roomType = 0;
    protected $zeroType = 9999;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\PersonRoomCommand')
//            ->addArgument('roomType', Argument::OPTIONAL, "roomType int [9999 9 6 12]")
            ->setDescription('refresh PersonRoomCommand sort bucket data');
    }


    private function initTypeIds()
    {
        $result = RoomTypeModelDao::getInstance()->getPersonTypeIds();
        $result[] = $this->zeroType;
        return array_values($result);
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln(sprintf('app\command\PersonRoomCommand execute entry date:%s', $this->getDateTime()));
        $typeIds = $this->initTypeIds();
        if (empty($typeIds)) {
            $output->writeln(sprintf('app\command\PersonRoomCommand init typeIds err date:%s fatal error not find person type ids', $this->getDateTime()));
        }
        foreach ($typeIds as $typeId) {
            $this->roomType = $typeId;
            $output->writeln(sprintf('app\command\PersonRoomCommand entry date:%s roomType:%d', $this->getDateTime(), $this->roomType));
            try {
                list($refreshNumber, $listCount) = $this->fitExeCute($output);
            } catch (\Exception $e) {
                $output->writeln(sprintf("app\command\PersonRoomCommand execute exception date:%s typeId:%s error:%s error trice:%s", $this->getDateTime(), $typeId, $e->getMessage(), $e->getTraceAsString()));
                continue;
            }
            $output->writeln(sprintf('app\command\PersonRoomCommand success end date:%s exec refreshNumber:%d dataNumber:%d roomType:%d', $this->getDateTime(), $refreshNumber, $listCount, $this->roomType));
            usleep(1000000);
        }
        // 指令输出
        $output->writeln(sprintf('app\command\PersonRoomCommand execute success end date:%s', $this->getDateTime()));
    }

    /**
     * @return array [刷新后zset的总数，count list的总数]
     */
    private function fitExeCute(Output $output)
    {
//        获取所有房间id
        $guildIds = $this->getGuildids();
        $output->writeln(sprintf('app\command\PersonRoomCommand entry date:%s guildIds:%s', $this->getDateTime(), json_encode($guildIds)));
//        根据房间id查询手动热度值，用户热度值，礼物热度值，聊天热度值,重新计算房间热度值，排序，
        $roomModelList = $this->roomIdToData($guildIds);
        $output->writeln(sprintf('app\command\PersonRoomCommand entry date:%s roomModelList:%s', $this->getDateTime(), json_encode($roomModelList)));
//        缓存公会房间明细数据
        $this->cacheData($guildIds, $roomModelList);
//        拆分：锁房的，没锁房的
        list($lockRoomModelList, $unLockRoomModelList) = $this->splitLockRoom($roomModelList);
        $unLockRoomModelList = $this->sortData($unLockRoomModelList);
        $lockRoomModelList = $this->sortData($lockRoomModelList);
//        重组zset数据
        $resetList = $this->reset($lockRoomModelList, $unLockRoomModelList);
//        更新热度值zset
        $output->writeln(sprintf('app\command\PersonRoomCommand entry date:%s resetList:%s', $this->getDateTime(), json_encode($resetList)));
        $refreshNumber = $this->refreshBucket($resetList);
        return [$refreshNumber, count($resetList)];
    }

//    private function joinParam(QueryRoomModel $itemRoomModel)
//    {
////        if (empty($itemRoomModel)) {
////            return;
////        }
////        if ($itemRoomModel->roomType == '狼人杀') {
////            $itemRoomModel->type = 3;
////            return;
////        }
////        if ($itemRoomModel->roomType == '你画我猜') {
////            $itemRoomModel->type = 4;
////            return;
////        }
////        if ($itemRoomModel->roomType == '谁是卧底') {
////            $itemRoomModel->type = 5;
////            return;
////        }
////        $itemRoomModel->type = 1;
//        $itemRoomModel->online = GuildQueryRoomModelCache::getInstance()->getOnlineRoomUserCount($itemRoomModel->roomId);
//        return;
//    }

    /**
     * @param $resetList
     * @return int  set的总数
     */
    private function refreshBucket($resetList)
    {
        $bucketObj = new GuildRoomBucket($this->roomType);
        return $bucketObj->storeList($resetList);
    }

    /**
     * @info 获取guild公会id数据
     * @return array
     */
    private function getGuildids()
    {
        $roomIds = RoomModelDao::getInstance()->getOnlinePersonRoomIdsForRoomType($this->roomType);
//        去除没有开启的个人房id
        $roomIdsResult = RoomModelDao::getInstance()->getShowRoomids($roomIds);
        if (config('config.appDev') === "dev") {
            if ($this->roomType === $this->zeroType) {
                $this->makeDevRoomid($roomIdsResult);
            }
        }
        return $roomIdsResult;
    }

    private function makeDevRoomid(&$re)
    {
        $result = array(
            0 => 124644,
            1 => 124643,
            2 => 124642,
            3 => 124641,
            4 => 124640,
            5 => 124639,
            6 => 124638,
            7 => 124637,
            8 => 124636,
            9 => 124635,
            10 => 124634,
            11 => 124633,
            12 => 124632,
            13 => 124631,
            14 => 124630,
            15 => 124628,
            16 => 124627,
            17 => 124626,
        );
        $re = array_merge($re, $result);
    }

}
