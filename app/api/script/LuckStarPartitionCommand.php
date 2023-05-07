<?php
/**
 * 元旦瓜分活动
 */

namespace app\api\script;

use app\domain\activity\luckStar\LuckStarService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class LuckStarPartitionCommand extends Command
{
    protected function configure()
    {
        $this->setName('LuckStarPartitionCommand')->setDescription('LuckStarPartitionCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        LuckStarService::getInstance()->luckStarPartition();
    }
}