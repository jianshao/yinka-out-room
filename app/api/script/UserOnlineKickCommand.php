<?php
/**
 * 定时任务
 * 每日福星榜送头像框
 */

namespace app\api\script;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\RedisCommon;

ini_set('set_time_limit', 0);

class UserOnlineKickCommand extends Command
{


    protected function configure()
    {
        $this->setName('UserOnlineKickCommand')->setDescription('UserOnlineKickCommand');
    }

    /**
     *用户在线列表剔出脚本
     */
    protected function execute(Input $input, Output $output)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $time = time();
        $listUser = $redis->zRevRange(sprintf('user_online_%s_list', 'all'),0,-1,true);
        foreach($listUser as $key => $val) {
            if ($val < $time-60) {
                $redis->zRem(sprintf('user_online_%s_list', 'all'), $key);
                $redis->zRem(sprintf('user_online_%s_list', 1), $key);
                $redis->zRem(sprintf('user_online_%s_list', 2), $key);
            }
        }
    }
}