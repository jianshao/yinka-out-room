<?php

namespace app\api\script\queue;

use app\common\server\QueuePop;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

/**
 * @info  sub消息订阅者
 * Class UserBucket
 * @package app\command
 * @command  php think QueuetaskCommand >> /tmp/QueuetaskCommand.log 2>&1
 */
class QueuetaskCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('QueuetaskCommand')
            ->setDescription('Queue task master');
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
        $output->info(sprintf('Queuetask Command execute date:%s', $this->getDateTime()));
        $this->initConf();
        while (true) {
            $this->fitRun($input, $output);
        }
        return;
    }


    private function fitRun(Input $input, Output $output)
    {
        try {
            $result = $this->fitExeCute($output);
        } catch (\Exception $e) {
            $output->writeln(sprintf("execute date:%s error:%s error trice:%s", $this->getDateTime(), $e->getMessage(), $e->getTraceAsString()));
            return;
        }
        // 指令输出
        $output->writeln(sprintf('QueuetaskCommand fitRun success end date:%s exec result:%s', $this->getDateTime(), $result));
    }

    /**
     * @info 初始化参数和配置
     * @param $sex
     */
    private function initConf()
    {
        ClassRegister::getInstance()->register('AppMsgHandler', AppMsgHandler::class);
    }

    private function fitExeCute(Output $output)
    {
        $obj = new QueuePop;
        $queueData = $obj->brPop();
        if (empty($queueData)) {
            return "";
        }
        $output->info(sprintf("QueuetaskCommand pop data:%s", json_encode($queueData)));
        $handle = $queueData['handle'];
        $method = $queueData['method'];
        return ClassRegister::getInstance()->handle($handle, $method, $queueData['data']);
    }


}
