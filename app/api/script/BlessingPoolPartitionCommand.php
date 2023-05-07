<?php
/**
 * 元旦瓜分活动
 */

namespace app\api\script;

use app\domain\activity\springFestival\SpringFestivalService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class BlessingPoolPartitionCommand extends Command
{
    protected function configure()
    {
        $this->setName('BlessingPoolPartitionCommand')->setDescription('BlessingPoolPartitionCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        SpringFestivalService::getInstance()->blessingPoolPartition();
    }
}