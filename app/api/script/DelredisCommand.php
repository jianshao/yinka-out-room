<?php
/**
 * 定时任务
 * del redis
 */

namespace app\api\script;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\RedisCommon;

ini_set('set_time_limit', 0);

class DelredisCommand extends Command
{


    protected function configure()
    {
        $this->setName('DelredisCommand')->setDescription('DelredisCommand');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        if (time() > 1600830000) {
            exit;
        }
    	$redis = RedisCommon::getInstance()->getRedis();
        $a = $redis->keys('self_rate_gold_rey_*');
        foreach ($a as $key => $value) {
            $redis->del($value);
        }
        $b = $redis->keys('self_rate_silver_rey_*');
        foreach ($b as $key => $value) {
            $redis->del($value);
        }

    }

   




}