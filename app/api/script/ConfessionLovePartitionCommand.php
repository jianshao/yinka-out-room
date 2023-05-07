<?php
/**
 * 520瓜分活动
 */

namespace app\api\script;

use app\domain\activity\confessionLove\ConfessionLoveService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class ConfessionLovePartitionCommand extends Command
{
    protected function configure()
    {
        $this->setName('ConfessionLovePartitionCommand')->setDescription('ConfessionLovePartitionCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        ConfessionLoveService::getInstance()->partitionTmpl();
    }
}