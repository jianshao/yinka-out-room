<?php

namespace app\api\script;

use app\domain\guild\cache\GuildRoomBucket;
use app\domain\guild\cache\HomeHotRoomBucket;
use app\domain\guild\cache\RecreationHotRoomBucket;
use app\domain\room\dao\RoomModelDao;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  房间推荐位相关
 * Class RecommendRoomCommand
 * @package app\command
 * @command  php think RecommendRoomCommand homeHotHandler  >> /tmp/RecommendRoomCommandhomeHotHandler.log 2>&1
 * @command  php think RecommendRoomCommand recreationHotHandler  >> /tmp/RecommendRoomCommandrecreationHotHandler.log 2>&1
 */
class RecommendRoomCommand extends RoomBaseCommond
{

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\RecommendRoomCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch func")
            ->setDescription('refresh RecommendRoomCommand sort bucket data');
    }

    protected function execute(Input $input, Output $output)
    {
        $func = $input->getArgument('func');
        if (is_null($func)) $func = 'handler';
        $output->writeln(sprintf('app\command\RecommendRoomCommand entry func:%s date:%s', $func, $this->getDateTime()));
        try {
            list($refreshNumber, $listCount) = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\RecommendRoomCommand execute exception func:%s date:%s error:%s error trice:%s", $func, $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        $output->writeln(sprintf('app\command\RecommendRoomCommand success end func:%s date:%s exec refreshNumber:%d dataNumber:%d', $func, $this->getDateTime(), $refreshNumber, $listCount));
        usleep(1000000);
    }

    private function handler()
    {
        echo 'handler';
        return [0, 0];
    }

    /**
     * @return array [刷新后zset的总数，count list的总数]
     */
    private function recreationHotHandler()
    {
//        获取所有公会房间id,展示的房间
        $guildIds = $this->getGuildids();
//        初始化房间推荐位的人气值相关
        $roomModelList = $this->recreationHotRoomIdsToData($guildIds);
//        拆分：过滤掉锁房间的
        $unLockRoomModelList = $this->trimLockRoom($roomModelList);
        $unLockRoomModelList = $this->sortData($unLockRoomModelList);
//        更新热度值zset
        $resetRoomDataList = $this->resetListToArr($unLockRoomModelList);
        $this->output->writeln(sprintf('app\command\RecommendRoomCommand recreationHotHandler resetListToArr date:%s resetList:%s', $this->getDateTime(), json_encode($resetRoomDataList)));
        $refreshNumber = $this->refreshRecreationHotBucket($unLockRoomModelList);
        return [$refreshNumber, count($unLockRoomModelList)];
    }


    /**
     * @return array [刷新后zset的总数，count list的总数]
     */
    private function homeHotHandler()
    {
//        获取所有公会房间id,展示的房间
        $guildIds = $this->getGuildids();
//        初始化房间推荐位的人气值相关
        $roomModelList = $this->homeRoomIdsToData($guildIds);
//        拆分：过滤掉锁房间的
        $unLockRoomModelList = $this->trimLockRoom($roomModelList);
        $unLockRoomModelList = $this->sortData($unLockRoomModelList);
//        更新热度值zset
        $resetRoomDataList = $this->resetListToArr($unLockRoomModelList);
        $this->output->writeln(sprintf('app\command\RecommendRoomCommand homeHotHandler resetListToArr date:%s resetList:%s', $this->getDateTime(), json_encode($resetRoomDataList)));
        $refreshNumber = $this->refreshHomeHotBucket($unLockRoomModelList);
        return [$refreshNumber, count($unLockRoomModelList)];
    }

    private function resetListToArr($roomModelList)
    {
        $result = [];
        foreach ($roomModelList as $itemRoomModel) {
            $result[] = $itemRoomModel->modelToData();
        }
        return $result;
    }

    private function homeRoomIdsToData($guildIds)
    {
        $result = [];
        foreach ($guildIds as $guildId) {
            $guildRoomCache = $this->getItemHomeHotData($guildId);
            if ($guildRoomCache->isEmptyRoom()) {
                continue;
            }
            $result[$guildId] = $guildRoomCache;
        }
        return $result;
    }

    private function recreationHotRoomIdsToData($guildIds)
    {
        $result = [];
        foreach ($guildIds as $guildId) {
            $guildRoomCache = $this->getItemRecreationHotData($guildId);
            if ($guildRoomCache->isEmptyRoom()) {
                continue;
            }
            $result[$guildId] = $guildRoomCache;
        }
        return $result;
    }


//    获取guild公会id数据
    private function getGuildids()
    {
        $roomType = 60;
        return RoomModelDao::getInstance()->getOnlineGuildRoomIdsForRoomType($roomType);
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


    private function refreshHomeHotBucket($resetList)
    {
        $bucketObj = new HomeHotRoomBucket();
        return $bucketObj->storeList($resetList);
    }


    private function refreshRecreationHotBucket($resetList)
    {
        $bucketObj = new RecreationHotRoomBucket();
        return $bucketObj->storeList($resetList);
    }

}
