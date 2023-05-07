<?php

namespace app\api\script;

use app\domain\guild\cache\GuildRoomCache;
use app\domain\guild\cache\HomeHotRoomCache;
use app\domain\guild\cache\RecreationHotRoomCache;
use app\domain\room\dao\HomeHotRoomModelDao;
use app\domain\room\dao\RecreationHotModelDao;
use app\domain\room\dao\RoomHotValueDao;
use app\domain\room\dao\RoomModelDao;
use app\service\RoomNotifyService;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  公会房间热度值扣减
 *     entry 入口 扣减
 *          礼物热度 1分钟 扣减  2%
 *          聊天热度 1分钟 扣减  3%
 * Class DeductRoomHotCommand
 * @package app\command
 * @command  php think DeductRoomHotCommand entry >> /tmp/DeductRoomHotCommand.log 2>&1
 * @command  php think DeductRoomHotCommand testHomeHot >> /tmp/TestHomeHotCommand.log 2>&1
 * @command  php think DeductRoomHotCommand entry_homeHot >> /tmp/DeductHomeHotRoomCommandHomeHot.log 2>&1
 * @command  php think DeductRoomHotCommand entry_recreationHot >> /tmp/DeductHomeHotRoomCommandRecreationHot.log 2>&1
 */
class DeductRoomHotCommand extends Command
{

    private $rate = '0.1'; //递减的比率值
    private $rate_second = '0.1'; //递减的比率值
    private $rate_home_hot = '0.05'; //首页热门推荐递减的比率值
    private $rate_recreation_hot = '0.05'; //娱乐人气推荐递减的比率值


    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\DeductRoomHotCommand')
            ->addArgument('func', Argument::OPTIONAL, "switch method [DeductGift DeductChat]")
            ->setDescription('DeductRoomHotCommand roomhot deduct');
    }

    private function getUnixTime()
    {
        return time();
    }

    private function getDateTime()
    {
        return date("Y-m-d H:i:s", $this->getUnixTime());
    }

    protected function execute(Input $input, Output $output)
    {
        $func = $input->getArgument('func') ? trim($input->getArgument('func')) : "";
        $output->writeln(sprintf('app\command\DeductRoomHotCommand entry date:%s method:%s', $this->getDateTime(), $func));
        try {
            $result = $this->{$func}();
        } catch (\Exception $e) {
            $output->writeln(sprintf("execute date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        // 指令输出
        $output->writeln(sprintf('app\command\DeductRoomHotCommand success end date:%s exec func:%s result:%d', $this->getDateTime(), $func, $result));
    }

    /**
     * @info entry room push
     * @param Output $output
     * @return bool
     */
    private function entry()
    {
        $guildIds = $this->getGuildids();
        foreach ($guildIds as $roomId) {
            if (empty($roomId)) {
                continue;
            }
            $this->executeHandler($roomId, $this->output);
        }
        return true;
    }

    /**
     * @info entry room push
     * @param Output $output
     * @return bool
     */
    private function entryPerson()
    {
        $guildIds = $this->getPersonids();
        foreach ($guildIds as $roomId) {
            if (empty($roomId)) {
                continue;
            }
            $this->executeHandler($roomId, $this->output);
        }
        return true;
    }

    private function getPersonids()
    {
        return RoomModelDao::getInstance()->getOnlinePersonRoomIdsForRoomType(9999);
    }


    /**
     * handler master entry
     * @param $roomId
     * @param Output $output
     * @return bool
     */
    private function executeHandler($roomId, Output $output)
    {
        $this->giftHandler($roomId, $output);
        $this->chatHandler($roomId, $output);
        $model = $this->initRoomHotModel($roomId);
        $sumHot = $model->getHotSumTpl();
        return $this->pushMsg($roomId, $sumHot, $output);
    }

    private function initConf()
    {

    }

    /**
     * @Info 扣减礼物热度值
     */
    private function DeductGift(Output $output)
    {
        $this->initGiftConf();
        $guildIds = $this->getGuildids();
        foreach ($guildIds as $roomId) {
            if (empty($roomId)) {
                continue;
            }
            $this->giftHandler($roomId, $output);
        }
        return true;
    }

    private function giftHandler($roomId, Output $output)
    {
//        获取礼物热度值
        $model = $this->initRoomHotModel($roomId);
        $oldHot = $model->getRoomHot()->getGiftHot();
//        如果已经是0不再扣减
        if ($model->getRoomHot()->getGiftHot() <= 0) {
            return true;
        }
//        计算礼物热度值 扣减
        $model->getRoomHot()->giftDecCalculate($this->rate_second);
//        更新热度值
        $this->upNewRoomHot($roomId, 'gift', $model->getRoomHot()->getGiftHot());
//        记录原来的值，更新的值，
        $newHot = $model->getRoomHot()->getGiftHot();
        $output->writeln(sprintf('app\command\DeductRoomHotCommand giftHandler success end date:%s exec roomId:%d oldHot:%d,newHot:%d', $this->getDateTime(), $roomId, $oldHot, $newHot));
        return true;
    }

    private function pushMsg($roomId, $newHot, Output $output)
    {
        $pushRe = RoomNotifyService::getInstance()->notifyHotChange($newHot, $roomId);
        return true;
    }

    private function upNewRoomHot($roomId, $field, $newHot)
    {
        RoomHotValueDao::getInstance()->setFieldValue($roomId, $field, $newHot);
    }


    private function initRoomHotModel($roomId)
    {
        $model = new GuildRoomCache($roomId);
        return $model;
    }

//    获取guild公会id数据
    private function getGuildids()
    {
        return RoomModelDao::getInstance()->getOnlineGuildRoomIds();
    }

    private function initGiftConf()
    {

    }

    /**
     * @info 扣减聊天热度值
     */
    private function DeductChat(Output $output)
    {
        $this->initGiftConf();

        $guildIds = $this->getGuildids();
        foreach ($guildIds as $roomId) {
            if (empty($roomId)) {
                continue;
            }
            $this->chatHandler($roomId, $output);
        }
        return true;
    }

    private function chatHandler($roomId, Output $output)
    {
//        获取礼物热度值
        $model = $this->initRoomHotModel($roomId);
        $oldHot = $model->getRoomHot()->getChatHot();
//        如果已经是0不再扣减
        if ($model->getRoomHot()->getChatHot() <= 0) {
            return true;
        }
//        计算礼物热度值 扣减
        $model->getRoomHot()->chatDecCalculate($this->rate);
//        更新热度值
        $this->upNewRoomHot($roomId, 'chat', $model->getRoomHot()->getChatHot());
//        记录原来的值，更新的值，
        $newHot = $model->getRoomHot()->getChatHot();
        $output->writeln(sprintf('app\command\DeductRoomHotCommand chatHandler success end date:%s exec roomId:%d oldHot:%d,newHot:%d', $this->getDateTime(), $roomId, $oldHot, $newHot));
        return true;
    }


    /**
     * @info 需求调整，已废弃
     * @return bool
     */
    private function entry_popular()
    {
        return true;
//        $guildIds = $this->getGuildids();
//        foreach ($guildIds as $roomId) {
//            if (empty($roomId)) {
//                continue;
//            }
//            $this->executePopular($roomId);
//        }
//        return true;
    }


    /**
     * @info 入口，首页热们推荐
     * @return bool
     */
    private function entry_homeHot()
    {
        $guildIds = $this->getGuildids();
        foreach ($guildIds as $roomId) {
            if (empty($roomId)) {
                continue;
            }
            try {
                $this->executeHomeHot($roomId);
            } catch (\Exception $e) {
                $this->output->writeln(sprintf("%s%s", $e->getCode(), $e->getTraceAsString()));
                continue;
            }
        }
        return true;
    }

    private function testHomeHot()
    {
        $roomId = 123775;
        $re = $this->executeHomeHot($roomId);
        var_dump($re);
        die;
    }

    private function executeHomeHot($roomId)
    {
        return $this->HomeHotGiftHandler($roomId);
    }

    private function HomeHotGiftHandler($roomId)
    {
        $model = new HomeHotRoomCache($roomId);
        $oldHot = $model->getRoomHot()->getGiftHot();
        if ($model->getRoomHot()->getGiftHot() <= 0) {
            return true;
        }
//        计算礼物热度值 扣减
        $model->getRoomHot()->giftDecCalculate($this->rate_home_hot);
        HomeHotRoomModelDao::getInstance()->setFieldValue($roomId, 'gift', $model->getRoomHot()->getGiftHot());
        $newHot = $model->getRoomHot()->getGiftHot();
        $this->output->writeln(sprintf('app\command\DeductRoomHotCommand HomeHotGiftHandler success end date:%s exec roomId:%d oldHot:%d,newHot:%d', $this->getDateTime(), $roomId, $oldHot, $newHot));
        return true;
    }


    /**
     * @info 入口，首页热们推荐
     * @return bool
     */
    private function entry_recreationHot()
    {
        $guildIds = $this->getGuildids();
        foreach ($guildIds as $roomId) {
            if (empty($roomId)) {
                continue;
            }
            try {
                $this->executeRecreationHot($roomId);
            } catch (\Exception $e) {
                $this->output->writeln(sprintf("%s%s", $e->getCode(), $e->getTraceAsString()));
                continue;
            }
        }
        return true;
    }

    private function testRecreationHot()
    {
        $roomId = 123775;
        $re = $this->executeRecreationHot($roomId);
        var_dump($re);
        die;
    }

    private function executeRecreationHot($roomId)
    {
        return $this->recreationHotGiftHandler($roomId);
    }

    /**
     * @param $roomId
     * @return bool
     */
    private function recreationHotGiftHandler($roomId)
    {
        $model = new RecreationHotRoomCache($roomId);
        $oldHot = $model->getRoomHot()->getGiftHot();
        if ($model->getRoomHot()->getGiftHot() <= 0) {
            return true;
        }
//        计算礼物热度值 扣减
        $model->getRoomHot()->giftDecCalculate($this->rate_recreation_hot);
        RecreationHotModelDao::getInstance()->setFieldValue($roomId, 'gift', $model->getRoomHot()->getGiftHot());
        $newHot = $model->getRoomHot()->getGiftHot();
        $this->output->writeln(sprintf('app\command\DeductRoomHotCommand recreationHotGiftHandler success end date:%s exec roomId:%d oldHot:%d,newHot:%d', $this->getDateTime(), $roomId, $oldHot, $newHot));
        return true;
    }

}
