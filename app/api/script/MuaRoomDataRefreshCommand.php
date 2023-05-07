<?php

namespace app\api\script;

use app\domain\room\dao\RoomModelDao;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  mua房间推荐位的room数据
 *        mua只排序，不做roomdata缓存
 * Class MuaRoomDataRefreshCommand
 * @package app\command
 * @command  php think MuaRoomDataRefreshCommand>> /tmp/MuaRoomDataRefreshCommand.log 2>&1
 */
class MuaRoomDataRefreshCommand extends RoomBaseCommond
{
    protected $roomType = 0;
    protected $zeroType = 9999;

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\MuaRoomDataRefreshCommand')
//            ->addArgument('roomType', Argument::OPTIONAL, "roomType int [9999 ]")
            ->setDescription('refresh MuaRoomDataRefreshCommand sort bucket data');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->roomType = 9999;
        $output->writeln(sprintf('app\command\MuaRoomDataRefreshCommand entry date:%s roomType:%d', $this->getDateTime(), $this->roomType));
        try {
            $listCount = $this->fitExeCute($output);
        } catch (\Exception $e) {
            $output->writeln(sprintf("execute date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\MuaRoomDataRefreshCommand success end date:%s exec dataNumber:%d roomType:%d', $this->getDateTime(), $listCount, $this->roomType));
    }

    /**
     * @param Output $output
     * @return int  //count list的总数
     */
    private function fitExeCute(Output $output)
    {
//        获取所有房间id
        $guildIds = $this->getGuildids();
        $output->writeln(sprintf('app\command\MuaRoomDataRefreshCommand entry date:%s guildIds:%s', $this->getDateTime(), json_encode($guildIds)));
        //        根据房间id查询手动热度值，用户热度值，礼物热度值，聊天热度值,重新计算房间热度值，排序，
        $roomModelList = $this->roomIdToData($guildIds);
        $output->writeln(sprintf('app\command\MuaRoomDataRefreshCommand entry date:%s roomModelList:%s', $this->getDateTime(), json_encode($roomModelList)));
//        缓存公会房间明细数据
        $this->cacheData($guildIds, $roomModelList);
        return count($guildIds);
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


//    获取guild公会id数据
    private function getGuildids()
    {
        return RoomModelDao::getInstance()->getMuaRefreshRoomIds();
    }


}
