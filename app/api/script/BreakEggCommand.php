<?php
/**
 * 定时任务
 * 账单
 */

namespace app\api\script;

use app\domain\game\turntable\baolv\TurntableBaolvService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\RedisCommon;

ini_set('set_time_limit', 0);

class BreakEggCommand extends Command
{


    protected function configure()
    {
        $this->setName('BreakEggCommand')->setDescription('BreakEggCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $subKey = 'turntable_baolv_task_sub';
        $redis = RedisCommon::getInstance()->getRedis();
        while (true) {
            $taskId = $redis->lPop($subKey);
            if ($taskId !== false) {
                TurntableBaolvService::getInstance()->runTaskById($taskId);
            }
            usleep(10);
        }

    }

   




}