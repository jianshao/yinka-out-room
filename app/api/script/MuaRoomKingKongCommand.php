<?php

namespace app\api\script;

use app\domain\guild\cache\MuaKingKongBucket;
use app\domain\room\dao\RoomModelDao;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  mua房间金刚位 数据清洗更新 维护个人热度backet 更新room数据缓存
 *        mua只排序，不做roomdata缓存
 * Class MuaRoomKingKongCommand
 * @package app\command
 * @command  php think MuaRoomKingKongCommand>> /tmp/MuaRoomKingKongCommand.log 2>&1
 */
class MuaRoomKingKongCommand extends RoomBaseCommond
{
    protected $roomType = 0;
    protected $zeroType = 9999;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\MuaRoomKingKongCommand')
//            ->addArgument('roomType', Argument::OPTIONAL, "roomType int [25 26 24 60]")
            ->setDescription('refresh MuaRoomKingKongCommand sort bucket data');
    }


    protected function execute(Input $input, Output $output)
    {
//        $this->roomType = $input->getArgument('roomType') ? intval($input->getArgument('roomType')) : $this->zeroType;
        $this->roomType = 9999;
        $output->writeln(sprintf('app\command\MuaRoomKingKongCommand entry date:%s roomType:%d', $this->getDateTime(), $this->roomType));
        try {
            list($refreshNumber, $listCount) = $this->fitExeCute($output);
        } catch (\Exception $e) {
            $output->writeln(sprintf("execute date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\MuaRoomKingKongCommand success end date:%s exec refreshNumber:%d dataNumber:%d roomType:%d', $this->getDateTime(), $refreshNumber, $listCount, $this->roomType));
    }

    /**
     * @return array [刷新后zset的总数，count list的总数]
     */
    private function fitExeCute(Output $output)
    {
//        获取所有房间id
        $guildIds = $this->getGuildids();
//        dd($guildIds);
        $output->writeln(sprintf('app\command\MuaRoomKingKongCommand entry date:%s guildIds:%s', $this->getDateTime(), json_encode($guildIds)));
//        根据房间id查询手动热度值，用户热度值，礼物热度值，聊天热度值,重新计算房间热度值，排序，
        $roomModelList = $this->roomIdToData($guildIds);
        $output->writeln(sprintf('app\command\MuaRoomKingKongCommand entry date:%s roomModelList:%s', $this->getDateTime(), json_encode($roomModelList)));
//        拆分：锁房的，没锁房的
        list($lockRoomModelList, $unLockRoomModelList) = $this->splitLockRoom($roomModelList);
        $unLockRoomModelList = $this->sortData($unLockRoomModelList);
        $lockRoomModelList = $this->sortData($lockRoomModelList);
//        重组zset数据
        $resetList = $this->reset($lockRoomModelList, $unLockRoomModelList);
//        更新热度值zset
        $output->writeln(sprintf('app\command\MuaRoomKingKongCommand entry date:%s resetList:%s', $this->getDateTime(), json_encode($resetList)));
        $refreshNumber = $this->refreshBucket($resetList);
        return [$refreshNumber, count($resetList)];
    }

    protected function roomIdToData($guildIds)
    {
        $result = [];
        foreach ($guildIds as $guildId) {
            $guildRoomCache = $this->getItemRoomHotData($guildId);
            $result[$guildId] = $guildRoomCache;
        }
        return $result;
    }


    /**
     * @param $resetList
     * @return int  set的总数
     */
    private function refreshBucket($resetList)
    {
        $bucketObj = new MuaKingKongBucket($this->roomType);
        return $bucketObj->storeList($resetList);
    }

//    获取guild公会id数据
    private function getGuildids()
    {
        return RoomModelDao::getInstance()->getMuaRoomKingKongRoomIds();
    }
}
