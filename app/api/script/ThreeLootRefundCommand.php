<?php
/**
 * 三人夺宝
 */

namespace app\api\script;

use app\domain\activity\duobao3\Duobao3Service;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

class ThreeLootRefundCommand extends Command
{


    protected function configure()
    {
        $this->setName('ThreeLootRefundCommand')->setDescription('ThreeLootRefundCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        for ($i = 0; $i < 3; $i++) {
            Duobao3Service::getInstance()->tuikuan(1);
            Duobao3Service::getInstance()->tuikuan(2);
            Duobao3Service::getInstance()->tuikuan(3);
        }
    }

   




}