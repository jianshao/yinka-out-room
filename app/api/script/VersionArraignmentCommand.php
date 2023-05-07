<?php

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
ini_set('set_time_limit', 0);

/**
 * @info 版本提审数据
 */
class VersionArraignmentCommand extends Command
{

    protected function configure()
    {
        // 指令配置
        $this->setName('app\command\VersionArraignmentCommand')
            ->setDescription('version Arraignment update data');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $this->handler();
        } catch (\Exception $e) {
            $output->writeln(sprintf("app\command\VersionArraignmentCommand execute date:%s error:%s error trice:%s", date('Y-m-d H:i:s',time()), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    private function handler()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $versionConf = Config::getInstance()->getVersionCheckConf();
        if(empty($versionConf)){
            return;
        }
        foreach($versionConf as $conf){
            $key = $conf['key'];
            $type = $conf['type'];
            $data = $conf['data'];
            if($key == '' || $type == '' || empty($data) || strpos($key,'version:') === false){
                continue;
            }
            $redis->del($key);
            if($type == 'set'){
                if(!empty($data)){
                    $redis->sAdd($key,...$data);
                }
            }else if($type == 'zset'){
                foreach($data as $zSetKey => $zSetValue){
                    $redis->zAdd($key,$zSetValue,$zSetKey);
                }
            }else{
                continue;
            }
        }
    }

}